<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ProgressHelper;
use PHPUnit\Framework\TestCase;

class ProgressHelperTest extends TestCase
{
    private ProgressHelper $progressHelper;

    protected function setUp(): void
    {
        $this->progressHelper = new ProgressHelper();
    }

    public function testGetVisualProgressTotalIsZero(): void
    {
        $this->assertSame(0.0, $this->progressHelper->getVisualProgress(0, 0));
        $this->assertSame(0.0, $this->progressHelper->getVisualProgress(10, 0));
    }

    public function testGetVisualProgressCurrentIsZero(): void
    {
        $this->assertSame(0.0, $this->progressHelper->getVisualProgress(0, 10));
    }

    public function testGetVisualProgressCurrentEqualsTotal(): void
    {
        $this->assertSame(100.0, $this->progressHelper->getVisualProgress(10, 10));
    }

    public function testGetVisualProgressIntermediateValues(): void
    {
        // 5 / 10 is 50%. Formula: (50^1.2) / (100^0.2) = 109.112... / 2.51188... = 43.527...
        // Let's use assertEqualsWithDelta to account for floating-point imprecision
        $result = $this->progressHelper->getVisualProgress(5, 10);
        $this->assertEqualsWithDelta(43.527, $result, 0.001);

        // 9 / 10 is 90%. Formula: (90^1.2) / (100^0.2) ≈ 88.123...
        $result2 = $this->progressHelper->getVisualProgress(9, 10);
        $this->assertEqualsWithDelta(88.123, $result2, 0.001);
    }
}
