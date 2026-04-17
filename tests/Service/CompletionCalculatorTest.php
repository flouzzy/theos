<?php

namespace App\Tests\Service;

use App\Entity\Completion;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Service\CompletionCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompletionCalculatorTest extends TestCase
{
    /** @var CompletionRepository&MockObject */
    private CompletionRepository $completionRepository;
    private CompletionCalculator $completionCalculator;

    protected function setUp(): void
    {
        $this->completionRepository = $this->createMock(CompletionRepository::class);
        $this->entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->completionCalculator = new CompletionCalculator($this->completionRepository, $this->entityManager);
    }

    public function testCalculateCompletionPercentageEmptyCourse(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $course->method('getModules')->willReturn(new ArrayCollection());

        $result = $this->completionCalculator->calculateCompletionPercentage($course, $user);

        $this->assertEquals(0, $result);
    }

    public function testCalculateCompletionPercentageCourseWithEmptyModule(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);

        $module->method('getLessons')->willReturn(new ArrayCollection());
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $result = $this->completionCalculator->calculateCompletionPercentage($course, $user);

        $this->assertEquals(0, $result);
    }

    public function testCalculateCompletionPercentageNoCompletion(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);
        $lesson = $this->createMock(Lesson::class);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $this->completionRepository->method('findCompletedLessonIdsByCourse')
            ->with($user, $course)
            ->willReturn([]);

        $result = $this->completionCalculator->calculateCompletionPercentage($course, $user);

        $this->assertEquals(0, $result);
    }

    public function testCalculateCompletionPercentagePartialCompletion(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);
        $lesson1 = $this->createMock(Lesson::class);
        $lesson1->method('getId')->willReturn(1);
        $lesson2 = $this->createMock(Lesson::class);
        $lesson2->method('getId')->willReturn(2);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson1, $lesson2]));
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $this->completionRepository->method('findCompletedLessonIdsByCourse')
            ->with($user, $course)
            ->willReturn([1]);

        $result = $this->completionCalculator->calculateCompletionPercentage($course, $user);

        $this->assertEquals(50.0, $result);
    }

    public function testCalculateCompletionPercentageFullCompletion(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(1);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $this->completionRepository->method('findCompletedLessonIdsByCourse')
            ->with($user, $course)
            ->willReturn([1]);

        $result = $this->completionCalculator->calculateCompletionPercentage($course, $user);

        $this->assertEquals(100.0, $result);
    }

    public function testCalculateCompletionPercentageIncompleteRecord(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);
        $lesson = $this->createMock(Lesson::class);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $this->completionRepository->method('findCompletedLessonIdsByCourse')
            ->with($user, $course)
            ->willReturn([]);

        $result = $this->completionCalculator->calculateCompletionPercentage($course, $user);

        $this->assertEquals(0, $result);
    }

    public function testCalculateCohortProgressEmptyCourses(): void
    {
        $user = $this->createMock(User::class);
        $coursesEntities = [];

        $this->completionRepository->method('findCompletedLessonIdsByUser')
            ->with($user)
            ->willReturn([]);

        $result = $this->completionCalculator->calculateCohortProgress($user, $coursesEntities);

        $this->assertEquals([
            'coursesData' => [],
            'globalProgress' => 0,
            'totalHours' => 0,
            'newLessonsCount' => 0,
        ], $result);
    }

    public function testCalculateCohortProgressWithCoursesAndCompletions(): void
    {
        $user = $this->createMock(User::class);

        $lesson1 = $this->createMock(Lesson::class);
        $lesson1->method('getId')->willReturn(1);
        $lesson1->method('getDuration')->willReturn(120);

        $lesson2 = $this->createMock(Lesson::class);
        $lesson2->method('getId')->willReturn(2);
        $lesson2->method('getDuration')->willReturn(60);

        $lesson3 = $this->createMock(Lesson::class);
        $lesson3->method('getId')->willReturn(3);
        $lesson3->method('getDuration')->willReturn(60);

        $module1 = $this->createMock(Module::class);
        $module1->method('getLessons')->willReturn(new ArrayCollection([$lesson1, $lesson2]));

        $module2 = $this->createMock(Module::class);
        $module2->method('getLessons')->willReturn(new ArrayCollection([$lesson3]));

        $course1 = $this->createMock(Course::class);
        $course1->method('getModules')->willReturn(new ArrayCollection([$module1]));

        $course2 = $this->createMock(Course::class);
        $course2->method('getModules')->willReturn(new ArrayCollection([$module2]));

        $this->completionRepository->method('findCompletedLessonIdsByUser')
            ->with($user)
            ->willReturn([1, 3]);

        $result = $this->completionCalculator->calculateCohortProgress($user, [$course1, $course2]);

        $this->assertCount(2, $result['coursesData']);

        $this->assertSame($course1, $result['coursesData'][0]['course']);
        $this->assertEquals(50.0, $result['coursesData'][0]['progress']);

        $this->assertSame($course2, $result['coursesData'][1]['course']);
        $this->assertEquals(100.0, $result['coursesData'][1]['progress']);

        $this->assertEquals(67.0, $result['globalProgress']); // 2 / 3 completed
        $this->assertEquals(4.0, $result['totalHours']); // (120+60+60)/60
        $this->assertEquals(1, $result['newLessonsCount']); // 3 - 2
    }

    public function testCalculateCohortProgressWithNullDuration(): void
    {
        $user = $this->createMock(User::class);

        $lesson1 = $this->createMock(Lesson::class);
        $lesson1->method('getId')->willReturn(1);
        $lesson1->method('getDuration')->willReturn(null); // testing null duration

        $module1 = $this->createMock(Module::class);
        $module1->method('getLessons')->willReturn(new ArrayCollection([$lesson1]));

        $course1 = $this->createMock(Course::class);
        $course1->method('getModules')->willReturn(new ArrayCollection([$module1]));

        $this->completionRepository->method('findCompletedLessonIdsByUser')
            ->with($user)
            ->willReturn([1]);

        $result = $this->completionCalculator->calculateCohortProgress($user, [$course1]);

        $this->assertEquals([
            'coursesData' => [
                [
                    'course' => $course1,
                    'progress' => 100.0
                ]
            ],
            'globalProgress' => 100.0,
            'totalHours' => 0.0, // null became 0
            'newLessonsCount' => 0,
        ], $result);
    }
}
