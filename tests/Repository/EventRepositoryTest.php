<?php

namespace App\Tests\Repository;

use App\Entity\Cohort;
use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class EventRepositoryTest extends TestCase
{
    public function testFindUpdatedEventsWithCohortGroupsConditionsCorrectly(): void
    {
        // Mock dependencies
        $registry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // Setup Repository
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $classMetadata->name = Event::class;

        $repository = new EventRepository($registry);

        // Expectation for createQueryBuilder
        $entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        // Allow method chaining
        $qb->method('select')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('orderBy')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        // The assertion: We expect correct grouping.
        // Current buggy code uses: where(...) then orWhere(...)
        // Fixed code should use: where(... OR ...) OR andWhere(... OR ...)

        // Assert that orWhere is NEVER called
        $qb->expects($this->never())
            ->method('orWhere');

        // Ensure where/andWhere return $qb
        $qb->expects($this->atLeastOnce())
            ->method('where')
            ->willReturn($qb);

        $qb->expects($this->atLeastOnce())
            ->method('andWhere')
            ->willReturn($qb);

        $cohort = new Cohort();
        $repository->findUpdatedEvents($cohort);
    }
}
