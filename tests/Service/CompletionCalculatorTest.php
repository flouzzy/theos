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
        $this->completionCalculator = new CompletionCalculator($this->completionRepository);
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

        $this->completionRepository->method('findOneBy')
            ->with(['user' => $user, 'lesson' => $lesson])
            ->willReturn(null);

        $result = $this->completionCalculator->calculateCompletionPercentage($course, $user);

        $this->assertEquals(0, $result);
    }

    public function testCalculateCompletionPercentagePartialCompletion(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);
        $lesson1 = $this->createMock(Lesson::class);
        $lesson2 = $this->createMock(Lesson::class);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson1, $lesson2]));
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $completion1 = $this->createMock(Completion::class);
        $completion1->method('isCompleted')->willReturn(true);

        $this->completionRepository->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($user, $lesson1, $lesson2, $completion1) {
                if ($criteria['user'] === $user && $criteria['lesson'] === $lesson1) {
                    return $completion1;
                }
                if ($criteria['user'] === $user && $criteria['lesson'] === $lesson2) {
                    return null;
                }
                return null;
            });

        $result = $this->completionCalculator->calculateCompletionPercentage($course, $user);

        $this->assertEquals(50.0, $result);
    }

    public function testCalculateCompletionPercentageFullCompletion(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);
        $lesson = $this->createMock(Lesson::class);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $completion = $this->createMock(Completion::class);
        $completion->method('isCompleted')->willReturn(true);

        $this->completionRepository->method('findOneBy')
            ->with(['user' => $user, 'lesson' => $lesson])
            ->willReturn($completion);

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

        $completion = $this->createMock(Completion::class);
        $completion->method('isCompleted')->willReturn(false);

        $this->completionRepository->method('findOneBy')
            ->with(['user' => $user, 'lesson' => $lesson])
            ->willReturn($completion);

        $result = $this->completionCalculator->calculateCompletionPercentage($course, $user);

        $this->assertEquals(0, $result);
    }
}
