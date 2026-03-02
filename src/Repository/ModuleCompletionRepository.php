<?php

namespace App\Repository;

use App\Entity\ModuleCompletion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuleCompletion>
 *
 * @method ModuleCompletion|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleCompletion|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method ModuleCompletion[]    findAll()
 * @method ModuleCompletion[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class ModuleCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleCompletion::class);
    }

    /**
     * Retourne le total des modules completétés par les utilisateurs
     * @return int|string|float|bool|null
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
    public function findWithScoreByUser(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.completed = true')
            ->andWhere('m.score IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
