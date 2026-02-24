<?php

namespace App\Tests\Repository;

use App\Entity\Completion;
use App\Repository\CompletionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class CompletionRepositoryTest extends TestCase
{
    public function testFindPaginated()
    {
        // Mock dependencies
        $registry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManager::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        // Setup expectations
        $registry->method('getManagerForClass')->willReturn($entityManager);

        // When ServiceEntityRepository is constructed, it calls $registry->getManagerForClass()
        // and then $em->getClassMetadata().
        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->name = Completion::class;

        // Mock createQueryBuilder chain
        $entityManager->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        // In findPaginated:
        // $this->createQueryBuilder('c') -> which calls $em->createQueryBuilder()
        // ->select('c') -> from('Completion', 'c') -> orderBy('c.id', 'DESC')

        // Since we are mocking createQueryBuilder on EM, we need to replicate what ServiceEntityRepository does.
        // Actually, ServiceEntityRepository::createQueryBuilder calls $em->createQueryBuilder() and sets select/from.

        // Let's mock the repository itself partially if possible, or just the EM calls.
        // But ServiceEntityRepository is concrete.

        // Testing specific method logic:
        // $query = $this->createQueryBuilder('c')->orderBy('c.id', 'DESC')->getQuery();

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('setFirstResult')
            ->with(0) // page 1, limit 50 -> offset 0
            ->willReturnSelf();

        $query->expects($this->once())
            ->method('setMaxResults')
            ->with(50)
            ->willReturnSelf();

        // Instantiate Repository
        $repository = new CompletionRepository($registry);

        // Call method
        $paginator = $repository->findPaginated(1, 50);

        $this->assertInstanceOf(\Doctrine\ORM\Tools\Pagination\Paginator::class, $paginator);
    }
}
