<?php

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginatorService
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function paginate(QueryBuilder $queryBuilder, int $limit = 20): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $page = 1;
        if ($request) {
            $page = max(1, $request->query->getInt('page', 1));
        }

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
