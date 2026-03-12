<?php
$content = file_get_contents('src/Repository/LessonRepository.php');
$search = <<<EOT
    /**
     * @return Lesson[] Returns an array of Lesson objects
     */
    public function findAllWithModules(): array
    {
        return \$this->createQueryBuilder('l')
            ->addSelect('m')
            ->leftJoin('l.module', 'm')
            ->addOrderBy('l.itemOrder', 'ASC')
            ->addOrderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
EOT;
$replace = <<<EOT
    /**
     * @return \Doctrine\ORM\QueryBuilder Returns a QueryBuilder for Lesson objects with modules
     */
    public function findAllWithModulesQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        return \$this->createQueryBuilder('l')
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
        return \$this->findAllWithModulesQueryBuilder()
            ->getQuery()
            ->getResult();
    }
EOT;

file_put_contents('src/Repository/LessonRepository.php', str_replace($search, $replace, $content));
