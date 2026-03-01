<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Select User count with verified
     * 
     */
    public function countVerifiedUsers()
    {

        return $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.isVerified = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Paginator<User>
     */
    public function findPaginatedUsers(int $page, int $limit = 20): Paginator
    {
        $query = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->getQuery();

        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    /**
     * @return User[]
     */
    public function findTopUsersByXp(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.xp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User[] $users
     * @return array<int, array<string, int>>
     */
    public function getCompletionCounts(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $userIds = array_map(fn(User $user) => $user->getId(), $users);

        $qb1 = $this->getEntityManager()->createQueryBuilder();
        $courseCounts = $qb1->select('IDENTITY(cc.user) as userId, COUNT(cc.id) as count')
            ->from('App\Entity\CourseCompletion', 'cc')
            ->where('cc.user IN (:userIds)')
            ->andWhere('cc.completed = true')
            ->setParameter('userIds', $userIds)
            ->groupBy('cc.user')
            ->getQuery()
            ->getResult();

        $qb2 = $this->getEntityManager()->createQueryBuilder();
        $moduleCounts = $qb2->select('IDENTITY(mc.user) as userId, COUNT(mc.id) as count')
            ->from('App\Entity\ModuleCompletion', 'mc')
            ->where('mc.user IN (:userIds)')
            ->andWhere('mc.completed = true')
            ->setParameter('userIds', $userIds)
            ->groupBy('mc.user')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($users as $user) {
            $counts[$user->getId()] = ['courses' => 0, 'modules' => 0];
        }

        foreach ($courseCounts as $row) {
            $counts[$row['userId']]['courses'] = (int) $row['count'];
        }

        foreach ($moduleCounts as $row) {
            $counts[$row['userId']]['modules'] = (int) $row['count'];
        }

        return $counts;
    }


}
