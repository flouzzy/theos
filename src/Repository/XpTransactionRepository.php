<?php

namespace App\Repository;

use App\Entity\XpTransaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XpTransaction>
 *
 * @method XpTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method XpTransaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method XpTransaction[]    findAll()
 * @method XpTransaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XpTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XpTransaction::class);
    }

    public function findXpGainedByUserBetween(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('x')
            ->select('SUM(x.amount)')
            ->where('x.user = :user')
            ->andWhere('x.createdAt BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
