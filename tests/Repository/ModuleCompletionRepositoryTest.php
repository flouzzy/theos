<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\ModuleCompletion;
use App\Entity\User;
use App\Repository\ModuleCompletionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ModuleCompletionRepositoryTest extends TestCase
{
    public function testFindByUserWithModuleAndCoursesBuildsCorrectQuery(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $classMetadata->name = ModuleCompletion::class;

        $repository = new ModuleCompletionRepository($registry);

        $entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $qb->method('select')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        // Specific calls for optimization
        $qb->expects($this->once())
            ->method('addSelect')
            ->with('m', 'c')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('join')
            ->with('mc.module', 'm')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('m.courses', 'c')
            ->willReturn($qb);

        $user = new User();
        $repository->findByUserWithModuleAndCourses($user);
    }
}
