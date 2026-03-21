<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RivalryService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RivalryServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private RivalryService $rivalryService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->rivalryService = new RivalryService($this->userRepository);
    }

    public function testGetRivals(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getXp')->willReturn(100);
        $user->method('getId')->willReturn(1);

        $rival1 = $this->createMock(User::class);
        $rival2 = $this->createMock(User::class);
        $expectedRivals = [$rival1, $rival2];

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $query = $this->createMock(AbstractQuery::class);

        $this->userRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('u.xp BETWEEN :min AND :max')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('u.id != :id')
            ->willReturnSelf();

        $queryBuilder->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnCallback(function($key, $value) use ($queryBuilder) {
                if ($key === 'min') {
                    $this->assertEquals(90.0, $value);
                } elseif ($key === 'max') {
                    $this->assertEquals(110.0, $value);
                } elseif ($key === 'id') {
                    $this->assertEquals(1, $value);
                }
                return $queryBuilder;
            });

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(3)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedRivals);

        $actualRivals = $this->rivalryService->getRivals($user);

        $this->assertSame($expectedRivals, $actualRivals);
    }
}
