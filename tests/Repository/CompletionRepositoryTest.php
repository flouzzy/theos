<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Completion;
use App\Entity\User;
use App\Repository\CompletionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class CompletionRepositoryTest extends TestCase
{
    public function testCountTotalDurationByUser(): void
    {
        // Mock dependencies
        $registry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $qb = $this->createMock(QueryBuilder::class);

        // Mock Query using createMock to avoid extending @final class manually
        // We need to use a partial mock or configure it properly if createMock fails
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturn(120);

        // Mock User
        $user = $this->createMock(User::class);

        // Setup Repository
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $classMetadata->name = Completion::class;

        $repository = new CompletionRepository($registry);

        // Expectation for createQueryBuilder
        $entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        // Setup QueryBuilder expectations
        $qb->method('select')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('join')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        // Assert that methods are called with correct arguments
        $qb->expects($this->exactly(2))
            ->method('select')
            ->with($this->logicalOr(
                $this->equalTo('c'),
                $this->equalTo('SUM(l.duration)')
            ));

        $qb->expects($this->once())
            ->method('join')
            ->with('c.lesson', 'l');

        $qb->expects($this->once())
            ->method('where')
            ->with('c.user = :user');

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('user', $user);

        // Expect query result
        // $query->expects($this->once()) -> method(...) is not available on real object unless partial mock
        // But since we use a real object (QueryMock), we don't need to mock the method.

        $result = $repository->countTotalDurationByUser($user);

        $this->assertEquals(120, $result);
    }
}
