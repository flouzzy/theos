<?php

namespace App\EventSubscriber;

use App\Entity\Cohort;
use App\Entity\Conversation;
use App\Event\CohortContentUnlockedEvent;
use App\Event\CourseSubscribedEvent;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CohortSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CourseSubscribedEvent::class => 'onCourseSubscribed',
            CohortContentUnlockedEvent::class => 'onCohortContentUnlocked',
        ];
    }

    public function onCohortContentUnlocked(CohortContentUnlockedEvent $event): void
    {
        $cohort = $event->getCohort();
        $course = $event->getCourse();

        foreach ($cohort->getUsers() as $user) {
            $this->notificationService->addNotification(
                $user,
                "🎁 Nouveau contenu débloqué !",
                sprintf("Un nouveau cours est disponible pour votre promotion %s : %s", $cohort->getTitle(), $course->getTitle()),
                $this->urlGenerator->generate('course_show', ['slug' => $course->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }
    }

    public function onCourseSubscribed(CourseSubscribedEvent $event): void
    {
        $course = $event->getCourse();
        $user = $event->getUser();

        // Check if user is already in a cohort linked to this course
        foreach ($user->getCohorts() as $userCohort) {
            if ($userCohort->getCourses()->contains($course)) {
                return; // Already in a cohort for this course
            }
        }

        $now = new \DateTimeImmutable();
        $month = $now->format('F');
        $year = $now->format('Y');
        $timezone = $user->getTimezone();
        $cohortTitle = sprintf('%s - %s %s (%s)', $course->getTitle(), $month, $year, $timezone);

        // Find existing cohort
        $cohortRepository = $this->entityManager->getRepository(Cohort::class);
        $cohort = $cohortRepository->findOneBy(['title' => $cohortTitle]);

        if (!$cohort) {
            $cohort = new Cohort();
            $cohort->setTitle($cohortTitle);
            $cohort->setDescription(sprintf('Cohort for %s starting in %s %s', $course->getTitle(), $month, $year));
            $cohort->setYear((int)$year);
            $cohort->setStartAt($now);
            $cohort->setStatus('active');
            $cohort->addCourse($course);

            $conversation = new Conversation();
            $this->entityManager->persist($conversation);
            $cohort->setConversation($conversation);

            $this->entityManager->persist($cohort);
        }

        if (!$cohort->getUsers()->contains($user)) {
            $cohort->addUser($user);
            $this->entityManager->persist($cohort);
            $this->entityManager->flush();
        }
    }
}
