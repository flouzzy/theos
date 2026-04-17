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

    public function testCalculateGlobalProgressForUsersEmptyArray(): void
    {
        $result = $this->completionCalculator->calculateGlobalProgressForUsers([]);
        $this->assertEquals([], $result);
    }

    public function testCalculateGlobalProgressForUsersEmptyCourses(): void
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getArrayResult')->willReturn([]);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('select')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('join')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $result = $this->completionCalculator->calculateGlobalProgressForUsers([$user1, $user2]);

        $this->assertEquals([1 => 0.0, 2 => 0.0], $result);
    }

    public function testCalculateGlobalProgressForUsersWithCourses(): void
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        $users = [$user1, $user2];

        // First Query: Get user courses
        $query1 = $this->createMock(\Doctrine\ORM\Query::class);
        $query1->method('getArrayResult')->willReturn([
            ['userId' => 1, 'courseId' => 10],
            ['userId' => 1, 'courseId' => 20],
            ['userId' => 2, 'courseId' => 10], // User 2 is only in course 10
        ]);

        $qb1 = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb1->method('select')->willReturn($qb1);
        $qb1->method('from')->willReturn($qb1);
        $qb1->method('join')->willReturn($qb1);
        $qb1->method('where')->willReturn($qb1);
        $qb1->method('setParameter')->willReturn($qb1);
        $qb1->method('getQuery')->willReturn($query1);

        // Second Query: Get total lessons per course
        $query2 = $this->createMock(\Doctrine\ORM\Query::class);
        $query2->method('getArrayResult')->willReturn([
            ['courseId' => 10, 'totalLessons' => 5],
            ['courseId' => 20, 'totalLessons' => 10],
        ]);

        $qb2 = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb2->method('select')->willReturn($qb2);
        $qb2->method('from')->willReturn($qb2);
        $qb2->method('join')->willReturn($qb2);
        $qb2->method('where')->willReturn($qb2);
        $qb2->method('setParameter')->willReturn($qb2);
        $qb2->method('groupBy')->willReturn($qb2);
        $qb2->method('getQuery')->willReturn($query2);

        // Third Query: Get completed lessons per user and course
        $query3 = $this->createMock(\Doctrine\ORM\Query::class);
        $query3->method('getArrayResult')->willReturn([
            ['userId' => 1, 'courseId' => 10, 'completedCount' => 2], // user 1, course 10: 2/5 = 40%
            ['userId' => 1, 'courseId' => 20, 'completedCount' => 10], // user 1, course 20: 10/10 = 100%
            ['userId' => 2, 'courseId' => 10, 'completedCount' => 1], // user 2, course 10: 1/5 = 20%
        ]);

        $qb3 = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb3->method('select')->willReturn($qb3);
        $qb3->method('from')->willReturn($qb3);
        $qb3->method('join')->willReturn($qb3);
        $qb3->method('where')->willReturn($qb3);
        $qb3->method('andWhere')->willReturn($qb3);
        $qb3->method('setParameter')->willReturn($qb3);
        $qb3->method('groupBy')->willReturn($qb3);
        $qb3->method('getQuery')->willReturn($query3);

        $this->entityManager->expects($this->exactly(3))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($qb1, $qb2, $qb3);

        $result = $this->completionCalculator->calculateGlobalProgressForUsers($users);

        // User 1: (40% + 100%) / 2 courses = 70%
        // User 2: (20%) / 1 course = 20%
        $this->assertEquals([1 => 70.0, 2 => 20.0], $result);
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
}
