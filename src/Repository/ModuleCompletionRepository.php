<?php

namespace App\Repository;

use App\Entity\ModuleCompletion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuleCompletion>
 *
 * @method ModuleCompletion|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleCompletion|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModuleCompletion[]    findAll()
 * @method ModuleCompletion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleCompletion::class);
    }

    /**
     * Retourne le total des modules completétés par les utilisateurs
     */
    public function countModuleCompletions()
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.completed = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return ModuleCompletion[]
     */
    public function findByUserWithModuleAndCourses(User $user): array
    {
        return $this->createQueryBuilder('mc')
            ->addSelect('m', 'c')
            ->join('mc.module', 'm')
            ->leftJoin('m.courses', 'c')
            ->where('mc.user = :user')
            ->andWhere('mc.completed = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return ModuleCompletion[] Returns an array of ModuleCompletion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ModuleCompletion
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
