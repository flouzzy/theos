<?php

namespace App\Repository;

use App\Entity\Lesson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 *
 * @method Lesson|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lesson|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Lesson[]    findAll()
 * @method Lesson[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    /**
     * @return Lesson[] Returns an array of Lesson objects
     */
    public function findAllWithModules(): array
    {
        return $this->createQueryBuilder('l')
            ->addSelect('m')
            ->leftJoin('l.module', 'm')
            ->getQuery()
            ->getResult();
    }

}
