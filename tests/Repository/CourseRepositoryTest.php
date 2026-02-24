<?php

namespace App\Tests\Repository;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class CourseRepositoryTest extends TestCase
{
    public function testFindCoursesWithModulesAndLessonsForUser(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $user = $this->createMock(User::class);

        // Mock ManagerRegistry to return EntityManager
        $registry->method('getManagerForClass')->willReturn($manager);

        // Mock EntityManager to return QueryBuilder
        $manager->method('getClassMetadata')->willReturn(new ClassMetadata(Course::class));
        $manager->method('createQueryBuilder')->willReturn($queryBuilder);

        // Mock QueryBuilder chain
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('join')->willReturn($queryBuilder);
        $queryBuilder->method('leftJoin')->willReturn($queryBuilder);
        $queryBuilder->method('addSelect')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        // Instantiate
        $repository = new CourseRepository($registry);

        // Expectations
        $queryBuilder->expects($this->once())->method('join')->with('c.users', 'u');
        $queryBuilder->expects($this->exactly(2))->method('leftJoin');
        $queryBuilder->expects($this->once())->method('addSelect')->with('m', 'l');
        $queryBuilder->expects($this->once())->method('where')->with('u = :user');
        $queryBuilder->expects($this->once())->method('setParameter')->with('user', $user);

        // Run
        $result = $repository->findCoursesWithModulesAndLessonsForUser($user);

        // Assert
        $this->assertIsArray($result);
    }
}
