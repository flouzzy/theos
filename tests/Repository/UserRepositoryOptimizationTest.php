<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class UserRepositoryOptimizationTest extends TestCase
{
    public function testGetCompletionCounts(): void
    {
        // Mock User
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);

        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        $users = [$user1, $user2];

        // Mock QueryBuilder and Query for Courses
        $queryCourse = $this->createMock(Query::class);
        $queryCourse->method('getResult')->willReturn([
            ['userId' => 1, 'count' => 5],
            ['userId' => 2, 'count' => 3],
        ]);

        $qbCourse = $this->createMock(QueryBuilder::class);
        $qbCourse->method('select')->willReturnSelf();
        $qbCourse->method('from')->with('App\Entity\CourseCompletion', 'cc')->willReturnSelf();
        $qbCourse->method('where')->willReturnSelf();
        $qbCourse->method('andWhere')->willReturnSelf();
        $qbCourse->method('setParameter')->willReturnSelf();
        $qbCourse->method('groupBy')->willReturnSelf();
        $qbCourse->method('getQuery')->willReturn($queryCourse);

        // Mock QueryBuilder and Query for Modules
        $queryModule = $this->createMock(Query::class);
        $queryModule->method('getResult')->willReturn([
            ['userId' => 1, 'count' => 10],
            // User 2 has no completed modules, so not in result set
        ]);

        $qbModule = $this->createMock(QueryBuilder::class);
        $qbModule->method('select')->willReturnSelf();
        $qbModule->method('from')->with('App\Entity\ModuleCompletion', 'mc')->willReturnSelf();
        $qbModule->method('where')->willReturnSelf();
        $qbModule->method('andWhere')->willReturnSelf();
        $qbModule->method('setParameter')->willReturnSelf();
        $qbModule->method('groupBy')->willReturnSelf();
        $qbModule->method('getQuery')->willReturn($queryModule);

        // Mock EntityManager
        $em = $this->createMock(EntityManagerInterface::class);
        // We expect 2 calls to createQueryBuilder
        $em->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($qbCourse, $qbModule);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = User::class;
        $em->method('getClassMetadata')->willReturn($classMetadata);

        // Mock Registry
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->with(User::class)->willReturn($em);

        // Instantiate Repository
        $repo = new UserRepository($registry);

        $result = $repo->getCompletionCounts($users);

        $expected = [
            1 => ['courses' => 5, 'modules' => 10],
            2 => ['courses' => 3, 'modules' => 0],
        ];

        $this->assertEquals($expected, $result);
    }
}
