<?php

namespace App\EventSubscriber;

use App\Entity\Cohort;
use App\Entity\Conversation;
use App\Event\CourseSubscribedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CohortSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CourseSubscribedEvent::class => 'onCourseSubscribed',
        ];
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
