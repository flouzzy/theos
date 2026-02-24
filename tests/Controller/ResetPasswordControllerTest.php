<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\ResetPasswordController;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordControllerTest extends TestCase
{
    public function testInstantiation(): void
    {
        $resetPasswordHelper = $this->createMock(ResetPasswordHelperInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $controller = new ResetPasswordController(
            $resetPasswordHelper,
            $entityManager,
            $logger,
            'test@example.com',
            'Test Sender'
        );

        $this->assertInstanceOf(ResetPasswordController::class, $controller);
    }
}
