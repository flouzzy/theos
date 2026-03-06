<?php

namespace App\Tests\Repository;

use App\Entity\CourseCompletion;
use App\Repository\CourseCompletionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CourseCompletionRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?CourseCompletionRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = static::getContainer()->get(CourseCompletionRepository::class);
    }

    public function testCountCoursesCompletions(): void
    {
        // Get initial count to handle databases with existing data
        $initialCount = (int) $this->repository->countCoursesCompletions();

        // Add 2 completed courses
        $completed1 = (new CourseCompletion())->setCompleted(true);
        $completed2 = (new CourseCompletion())->setCompleted(true);

        // Add 1 incomplete course
        $incomplete = (new CourseCompletion())->setCompleted(false);

        $this->entityManager->persist($completed1);
        $this->entityManager->persist($completed2);
        $this->entityManager->persist($incomplete);
        $this->entityManager->flush();

        // Assert that the count increased by exactly 2
        $newCount = (int) $this->repository->countCoursesCompletions();

        $this->assertEquals($initialCount + 2, $newCount, 'The count of completed courses should have increased by 2.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }
}
