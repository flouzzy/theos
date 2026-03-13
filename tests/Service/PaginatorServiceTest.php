<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\PaginatorService;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginatorServiceTest extends TestCase
{
    private RequestStack&MockObject $requestStack;
    private QueryBuilder&MockObject $queryBuilder;
    private Query&MockObject $query;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $this->query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setFirstResult', 'setMaxResults', 'getResult', 'getSQL', 'getHydrationMode'])
            ->getMock();

        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->queryBuilder->method('getRootAliases')->willReturn(['e']);
    }

    public function testPaginateWithoutRequestReturnsDefaultPage(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        // Expect setFirstResult(0) and setMaxResults(15) to be called on the Query object
        $this->query->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf();

        $this->query->expects($this->once())
            ->method('setMaxResults')
            ->with(15)
            ->willReturnSelf();

        $paginatorService = new PaginatorService($this->requestStack);

        $this->expectException(\Error::class);
        $paginatorService->paginate($this->queryBuilder, 15);
    }

    public function testPaginateWithRequestReturnsCorrectPage(): void
    {
        $request = new Request(['page' => 3]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->query->expects($this->once())
            ->method('setFirstResult')
            ->with(40) // 20 * (3 - 1)
            ->willReturnSelf();

        $this->query->expects($this->once())
            ->method('setMaxResults')
            ->with(20)
            ->willReturnSelf();

        $paginatorService = new PaginatorService($this->requestStack);

        $this->expectException(\Error::class);
        $paginatorService->paginate($this->queryBuilder, 20);
    }

    public function testPaginateWithInvalidRequestPageReturnsDefaultPage(): void
    {
        // Test with page < 1 (e.g. 0 or negative)
        $request = new Request(['page' => -5]);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->query->expects($this->once())
            ->method('setFirstResult')
            ->with(0) // Should default back to 1: max(1, -5) = 1. So (1 - 1) * 20 = 0
            ->willReturnSelf();

        $this->query->expects($this->once())
            ->method('setMaxResults')
            ->with(20)
            ->willReturnSelf();

        $paginatorService = new PaginatorService($this->requestStack);

        $this->expectException(\Error::class);
        $paginatorService->paginate($this->queryBuilder, 20);
    }
}
