<?php

namespace App\Tests\Repository;

use App\Entity\Completion;
use App\Entity\User;
use App\Repository\CompletionRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class CompletionRepositoryTest extends TestCase
{
    public function testFindCompletedLessonIdsByUser(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $user = $this->createMock(User::class);

        $registry->method('getManagerForClass')->willReturn($manager);
        $manager->method('getClassMetadata')->willReturn(new ClassMetadata(Completion::class));
        $manager->method('createQueryBuilder')->willReturn($queryBuilder);

        // Chain mocking
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getSingleColumnResult')->willReturn([1, 2, 3]);

        // Mock `select` calls
        $selectCalls = [];
        $queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->willReturnCallback(function($arg) use (&$selectCalls, $queryBuilder) {
                $selectCalls[] = $arg;
                return $queryBuilder;
            });

        // Expectations
        $queryBuilder->expects($this->once())->method('where')->with('c.user = :user');
        $queryBuilder->expects($this->once())->method('andWhere')->with('c.completed = true');
        $queryBuilder->expects($this->once())->method('setParameter')->with('user', $user);

        // Instantiate
        $repository = new CompletionRepository($registry);

        // Run
        $result = $repository->findCompletedLessonIdsByUser($user);

        // Assert
        $this->assertEquals(['c', 'IDENTITY(c.lesson)'], $selectCalls);
        $this->assertEquals([1, 2, 3], $result);
    }
}
