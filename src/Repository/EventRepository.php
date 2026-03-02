<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Cohort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @phpstan-method Event|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @phpstan-method Event[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[] Returns an array of Event objects for a specific cohort or public
     */
    public function findUpdatedEvents(?Cohort $cohort, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($cohort) {
            $qb->andWhere('(e.cohort IS NULL OR e.cohort = :cohort)')
               ->setParameter('cohort', $cohort);
        } else {
            $qb->andWhere('e.cohort IS NULL');
        }

        return $qb
            ->andWhere('e.startAt >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
