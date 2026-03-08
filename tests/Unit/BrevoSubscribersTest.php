<?php

namespace App\Tests\Unit;

use App\Entity\Cohort;
use App\Entity\Course;
use App\Entity\User;
use App\Event\TrainingCompletionEvent;
use App\Event\UserVerifiedEvent;
use App\EventSubscriber\RegistrationSubscriber;
use App\EventSubscriber\TrainingCompletionSubscriber;
use App\Repository\CourseCompletionRepository;
use App\Service\BrevoApi;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class BrevoSubscribersTest extends TestCase
{
    public function testRegistrationSubscriberOnUserVerified(): void
    {
        $user = $this->createMock(User::class);
        $brevoApi = $this->createMock(BrevoApi::class);
        
        $brevoApi->expects($this->once())
            ->method('addContactToOnboardedList')
            ->with($user);

        $subscriber = new RegistrationSubscriber($brevoApi);
        $event = new UserVerifiedEvent($user);
        
        $subscriber->onUserVerified($event);
    }

    public function testTrainingCompletionSubscriberOnTrainingCompleted(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $cohort = $this->createMock(Cohort::class);
        $brevoApi = $this->createMock(BrevoApi::class);
        $courseCompletionRepository = $this->createMock(CourseCompletionRepository::class);

        $course->method('getId')->willReturn(1);
        $user->method('getCohorts')->willReturn(new ArrayCollection([$cohort]));
        $cohort->method('getCourses')->willReturn(new ArrayCollection([$course]));

        // Mock completed course IDs to include our course
        $courseCompletionRepository->method('findCompletedCourseIdsForUser')
            ->willReturn([1]);

        $brevoApi->expects($this->once())
            ->method('moveToAlumniList')
            ->with($user);

        $subscriber = new TrainingCompletionSubscriber($brevoApi, $courseCompletionRepository);
        $event = new TrainingCompletionEvent($user, $course);
        
        $subscriber->onTrainingCompleted($event);
    }
}
