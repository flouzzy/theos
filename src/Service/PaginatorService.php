<?php

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginatorService
{
    public function paginate(QueryBuilder $queryBuilder, int $page = 1, int $limit = 20): array
    {
        $paginator = new Paginator($queryBuilder);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        $totalItems = count($paginator);
        $totalPages = (int) ceil($totalItems / $limit);

        return [
            'data' => iterator_to_array($paginator),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
        ];
    }
}
