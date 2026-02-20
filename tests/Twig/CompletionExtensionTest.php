<?php

namespace App\Tests\Twig;

use App\Entity\Course;
use App\Entity\User;
use App\Service\CompletionCalculator;
use App\Twig\CompletionExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CompletionExtensionTest extends TestCase
{
    private CompletionCalculator $completionCalculator;
    private CompletionExtension $completionExtension;

    protected function setUp(): void
    {
        $this->completionCalculator = $this->createMock(CompletionCalculator::class);
        $this->completionExtension = new CompletionExtension($this->completionCalculator);
    }

    public function testGetFilters(): void
    {
        $filters = $this->completionExtension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('completion_percentage', $filters[0]->getName());
        $this->assertSame([$this->completionExtension, 'calculateCompletionPercentage'], $filters[0]->getCallable());
    }

    public function testGetFunctions(): void
    {
        $functions = $this->completionExtension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('completion_percentage', $functions[0]->getName());
        $this->assertSame([$this->completionExtension, 'calculateCompletionPercentage'], $functions[0]->getCallable());
    }

    public function testCalculateCompletionPercentage(): void
    {
        $course = $this->createMock(Course::class);
        $user = $this->createMock(User::class);
        $expectedPercentage = 75.5;

        $this->completionCalculator->expects($this->once())
            ->method('calculateCompletionPercentage')
            ->with($course, $user)
            ->willReturn($expectedPercentage);

        $result = $this->completionExtension->calculateCompletionPercentage($course, $user);

        $this->assertSame($expectedPercentage, $result);
    }
}
