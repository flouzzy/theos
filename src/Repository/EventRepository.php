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
        $qb = $this->createQueryBuilder('e')
            ->where('e.cohort IS NULL'); // Public events

        if ($cohort) {
            $qb->orWhere('e.cohort = :cohort')
               ->setParameter('cohort', $cohort);
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
