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
     * @return Event[] Returns an array of Event objects for a specific calendar, filtered and sorted
     */
    public function findFilteredEvents(\App\Entity\Calendar $calendar, string $query = '', ?int $typeId = null, string $sortBy = 'date_asc'): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.calendar = :calendar')
            ->setParameter('calendar', $calendar);

        if ($query !== '') {
            $qb->andWhere('e.title LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($typeId !== null) {
            $qb->andWhere('e.type = :typeId')
               ->setParameter('typeId', $typeId);
        }

        switch ($sortBy) {
            case 'date_desc':
                $qb->orderBy('e.startAt', 'DESC');
                break;
            case 'name_asc':
                $qb->orderBy('e.title', 'ASC');
                break;
            case 'name_desc':
                $qb->orderBy('e.title', 'DESC');
                break;
            case 'date_asc':
            default:
                $qb->orderBy('e.startAt', 'ASC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects for a specific cohort or public
     */
    public function findUpdatedEvents(?Cohort $cohort, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($cohort && $cohort->getCalendar()) {
            $qb->andWhere('(e.calendar = :calendar OR e.calendar IS NULL)')
               ->setParameter('calendar', $cohort->getCalendar());
        } else {
            // If no cohort or no calendar, we only return events that have NO calendar (global/public events)
            $qb->andWhere('e.calendar IS NULL');
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
