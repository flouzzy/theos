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
     * @return \Doctrine\ORM\QueryBuilder Returns a QueryBuilder for Lesson objects with modules
     */
    public function findAllWithModulesQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->addSelect('m')
            ->leftJoin('l.module', 'm')
            ->addOrderBy('l.itemOrder', 'ASC')
            ->addOrderBy('l.id', 'ASC');
    }

    /**
     * @return Lesson[] Returns an array of Lesson objects
     */
    public function findAllWithModules(): array
    {
        return $this->findAllWithModulesQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    public function findEfficacyStatsByModule(\App\Entity\Module $module): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.id', 'l.title', 'AVG(c.score) as avgScore', 'COUNT(c.id) as completionCount')
            ->leftJoin('l.completions', 'c')
            ->where('l.module = :module')
            ->andWhere('c.completed = true OR c.id IS NULL')
            ->setParameter('module', $module)
            ->groupBy('l.id')
            ->getQuery()
            ->getResult();
    }

    public function findFirstUncompletedAudioLessonWithContext(\App\Entity\User $user): ?array
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('l AS lesson', 'm AS module', 'c AS course')
            ->from(\App\Entity\User::class, 'u')
            ->join('u.courses', 'c')
            ->join('c.modules', 'm')
            ->join('m.lessons', 'l')
            ->where('u = :user')
            ->andWhere('l.audioPath IS NOT NULL AND l.audioPath != \'\'')
            ->andWhere('NOT EXISTS (
                SELECT comp FROM App\Entity\Completion comp
                WHERE comp.lesson = l AND comp.user = :user AND comp.completed = true
            )')
            ->orderBy('c.id', 'ASC')
            ->addOrderBy('m.itemOrder', 'ASC')
            ->addOrderBy('l.itemOrder', 'ASC')
            ->setMaxResults(1)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return !empty($result) ? $result[0] : null;
    }
}
