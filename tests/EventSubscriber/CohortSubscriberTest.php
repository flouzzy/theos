<?php

namespace App\Tests\EventSubscriber;

use App\Entity\Cohort;
use App\Entity\Course;
use App\Entity\User;
use App\Event\CourseSubscribedEvent;
use App\EventSubscriber\CohortSubscriber;
use App\Service\NotificationService;
use App\Service\WebhookService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CohortSubscriberTest extends TestCase
{
    public function testOnCourseSubscribedUserAlreadyInCohort(): void
    {
        $course = new Course();
        $course->setTitle('Test Course');

        $user = new User();
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setTimezone('Europe/Paris');

        $cohort = new Cohort();
        $cohort->addCourse($course);
        $user->addCohort($cohort);

        $event = new CourseSubscribedEvent($course, $user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('getRepository');
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $notificationService = $this->createMock(NotificationService::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $webhookService = $this->createMock(WebhookService::class);

        $subscriber = new CohortSubscriber($entityManager, $notificationService, $urlGenerator, $webhookService);
        $subscriber->onCourseSubscribed($event);
    }

    public function testOnCourseSubscribedExistingCohort(): void
    {
        $course = new Course();
        $course->setTitle('Test Course');

        $user = new User();
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setTimezone('Europe/Paris');

        $now = new \DateTimeImmutable();
        $month = $now->format('F');
        $year = $now->format('Y');
        $timezone = $user->getTimezone();
        $cohortTitle = sprintf('%s - %s %s (%s)', $course->getTitle(), $month, $year, $timezone);

        $existingCohort = new Cohort();
        $existingCohort->setTitle($cohortTitle);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => $cohortTitle])
            ->willReturn($existingCohort);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Cohort::class)
            ->willReturn($repository);

        // Expect persisting the cohort as the user gets added to it
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($existingCohort);

        $entityManager->expects($this->once())
            ->method('flush');

        $notificationService = $this->createMock(NotificationService::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $webhookService = $this->createMock(WebhookService::class);

        $event = new CourseSubscribedEvent($course, $user);
        $subscriber = new CohortSubscriber($entityManager, $notificationService, $urlGenerator, $webhookService);
        $subscriber->onCourseSubscribed($event);

        $this->assertTrue($existingCohort->getUsers()->contains($user));
    }

    public function testOnCourseSubscribedNewCohort(): void
    {
        $course = new Course();
        $course->setTitle('Test Course');

        $user = new User();
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setTimezone('Europe/Paris');

        $now = new \DateTimeImmutable();
        $month = $now->format('F');
        $year = $now->format('Y');
        $timezone = $user->getTimezone();
        $cohortTitle = sprintf('%s - %s %s (%s)', $course->getTitle(), $month, $year, $timezone);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => $cohortTitle])
            ->willReturn(null); // No existing cohort

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Cohort::class)
            ->willReturn($repository);

        $entityManager->expects($this->exactly(3))
            ->method('persist'); // Persists Conversation, then Cohort, then Cohort again when User is added

        $entityManager->expects($this->once())
            ->method('flush');

        $event = new CourseSubscribedEvent($course, $user);
        $notificationService = $this->createMock(NotificationService::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $webhookService = $this->createMock(WebhookService::class);

        $subscriber = new CohortSubscriber($entityManager, $notificationService, $urlGenerator, $webhookService);
        $subscriber->onCourseSubscribed($event);
    }
}
