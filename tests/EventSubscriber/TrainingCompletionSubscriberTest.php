<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\Cohort;
use App\Entity\Course;
use App\Entity\User;
use App\Event\TrainingCompletionEvent;
use App\EventSubscriber\TrainingCompletionSubscriber;
use App\Repository\CourseCompletionRepository;
use App\Service\BrevoApi;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class TrainingCompletionSubscriberTest extends TestCase
{
    public function testOnTrainingCompletedMovesToAlumniWhenAllCoursesFinished(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $event = new TrainingCompletionEvent($user, $course);

        $course1 = $this->createMock(Course::class);
        $course1->method('getId')->willReturn(1);
        $course2 = $this->createMock(Course::class);
        $course2->method('getId')->willReturn(2);

        $cohort = $this->createMock(Cohort::class);
        $cohort->method('getCourses')->willReturn(new ArrayCollection([$course1, $course2]));

        $user->method('getCohorts')->willReturn(new ArrayCollection([$cohort]));

        $repository = $this->createMock(CourseCompletionRepository::class);
        $repository->method('findCompletedCourseIdsForUser')->willReturn([1, 2]);

        $brevoApi = $this->createMock(BrevoApi::class);
        $brevoApi->expects($this->once())
            ->method('moveToAlumniList')
            ->with($user);

        $subscriber = new TrainingCompletionSubscriber($brevoApi, $repository);
        $subscriber->onTrainingCompleted($event);
    }

    public function testOnTrainingCompletedDoesNotMoveIfCoursesIncomplete(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $event = new TrainingCompletionEvent($user, $course);

        $course1 = $this->createMock(Course::class);
        $course1->method('getId')->willReturn(1);
        $course2 = $this->createMock(Course::class);
        $course2->method('getId')->willReturn(2);

        $cohort = $this->createMock(Cohort::class);
        $cohort->method('getCourses')->willReturn(new ArrayCollection([$course1, $course2]));

        $user->method('getCohorts')->willReturn(new ArrayCollection([$cohort]));

        $repository = $this->createMock(CourseCompletionRepository::class);
        $repository->method('findCompletedCourseIdsForUser')->willReturn([1]); // Only 1 out of 2

        $brevoApi = $this->createMock(BrevoApi::class);
        $brevoApi->expects($this->never())
            ->method('moveToAlumniList');

        $subscriber = new TrainingCompletionSubscriber($brevoApi, $repository);
        $subscriber->onTrainingCompleted($event);
    }
}
