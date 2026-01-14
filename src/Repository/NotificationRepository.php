<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findAllUnread(): array
    {
        // Find all notification attached with current user and unread
        // or where user is null
        // order by creation date

        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user OR n.user IS NULL')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('user', $this->security->getUser())
            ->setParameter('isRead', false)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnread(): int
    {
        $user = $this->security->getUser();
        if (!$user) {
            return 0;
        }

        return $this->createQueryBuilder('n')
            ->select('count(n.id)')
            ->andWhere('n.user = :user OR n.user IS NULL')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllByUser(User $user, $limit = null): array
    {
        // return $this->findBy(['user' => $user], $orderBy, $limit, $offset);
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user OR n.user IS NULL')
            ->setParameter('user', $user)
            ->setMaxResults($limit)
            ->addOrderBy('n.createdAt', 'DESC')
            ->addOrderBy('n.isRead', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
