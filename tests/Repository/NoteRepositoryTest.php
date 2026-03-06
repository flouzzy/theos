<?php

namespace App\Tests\Repository;

use App\Entity\Lesson;
use App\Entity\Note;
use App\Entity\User;
use App\Repository\NoteRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class NoteRepositoryTest extends TestCase
{
    public function testFindUserNotesByLesson(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $lesson = $this->createMock(Lesson::class);
        $user = $this->createMock(User::class);

        // Mock ManagerRegistry to return EntityManager
        $registry->method('getManagerForClass')->willReturn($manager);

        // Mock EntityManager to return QueryBuilder
        $manager->method('getClassMetadata')->willReturn(new ClassMetadata(Note::class));
        $manager->method('createQueryBuilder')->willReturn($queryBuilder);

        // Mock QueryBuilder chain
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $andWhereCalls = [];
        $queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function($arg) use (&$andWhereCalls, $queryBuilder) {
                $andWhereCalls[] = $arg;
                return $queryBuilder;
            });

        $setParameterCalls = [];
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function($key, $value) use (&$setParameterCalls, $queryBuilder) {
                $setParameterCalls[] = [$key, $value];
                return $queryBuilder;
            });

        // Instantiate
        $repository = new NoteRepository($registry);

        // Run
        $result = $repository->findUserNotesByLesson($lesson, $user);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(['n.user = :user', 'n.lesson = :lesson'], $andWhereCalls);
        $this->assertEquals([['user', $user], ['lesson', $lesson]], $setParameterCalls);
    }
}
