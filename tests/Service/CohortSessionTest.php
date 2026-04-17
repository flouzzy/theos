<?php

namespace App\Tests\Service;

use App\Entity\Cohort;
use App\Entity\User;
use App\Repository\CohortRepository;
use App\Service\CohortSession;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CohortSessionTest extends TestCase
{
    private Security $security;
    private CohortRepository $cohortRepository;
    private RequestStack $requestStack;
    private CohortSession $cohortSession;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->cohortRepository = $this->createMock(CohortRepository::class);
        $session = new Session(new MockArraySessionStorage());
        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->setSession($session);
        
        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);

        $this->cohortSession = new CohortSession(
            $this->requestStack,
            $this->security,
            $this->cohortRepository
        );
    }

    public function testGetSelectedCohortReturnsNullIfNoUser(): void
    {
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->assertNull($this->cohortSession->getSelectedCohort());
    }

    public function testGetSelectedCohortReturnsNullIfUserIsNotAppUser(): void
    {
        $mockUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($mockUser);

        $this->assertNull($this->cohortSession->getSelectedCohort());
    }

    public function testGetSelectedCohortDefaultsToFirstUserCohort(): void
    {
        $user = new User();
        $cohort = new Cohort();
        $cohort->setTitle('Promo 1');
        $user->addCohort($cohort);

        $this->security->method('getUser')->willReturn($user);
        
        $selected = $this->cohortSession->getSelectedCohort();
        $this->assertSame($cohort, $selected);
        $this->assertEquals($cohort->getId(), $this->requestStack->getSession()->get('active_cohort_id'));
    }

    public function testSetSelectedCohortUpdatesSession(): void
    {
        $cohort = new Cohort();
        $this->setSharedEntityId($cohort, 42); // Assumes we have reflection trick if needed, or simply let mock object

        $this->cohortSession->setSelectedCohort($cohort);
        $this->assertEquals($cohort->getId(), $this->requestStack->getSession()->get('active_cohort_id'));
    }
    

    public function testGetSelectedCohortReturnsCachedCohortImmediately(): void
    {
        $cohort = new Cohort();
        $this->cohortSession->setSelectedCohort($cohort);

        // Expect no interaction with security since it's cached
        $this->security->expects($this->never())->method('getUser');

        $this->assertSame($cohort, $this->cohortSession->getSelectedCohort());
    }

    public function testGetSelectedCohortReturnsSessionCohortIfValidAndUserBelongsToIt(): void
    {
        $user = new User();
        $cohort = new Cohort();
        $this->setSharedEntityId($cohort, 99);
        $user->addCohort($cohort);

        $this->security->method('getUser')->willReturn($user);
        $this->requestStack->getSession()->set('active_cohort_id', 99);

        $this->cohortRepository->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn($cohort);

        $this->assertSame($cohort, $this->cohortSession->getSelectedCohort());
    }

    public function testGetSelectedCohortFallsBackToFirstCohortIfSessionCohortNotFound(): void
    {
        $user = new User();
        $firstCohort = new Cohort();
        $firstCohort->setTitle('First Cohort');
        $user->addCohort($firstCohort);

        $this->security->method('getUser')->willReturn($user);
        $this->requestStack->getSession()->set('active_cohort_id', 99);

        // Repository returns null
        $this->cohortRepository->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $this->assertSame($firstCohort, $this->cohortSession->getSelectedCohort());
    }

    public function testGetSelectedCohortFallsBackToFirstCohortIfUserDoesNotBelongToSessionCohort(): void
    {
        $user = new User();
        $firstCohort = new Cohort();
        $firstCohort->setTitle('First Cohort');
        $user->addCohort($firstCohort);

        $sessionCohort = new Cohort();
        $this->setSharedEntityId($sessionCohort, 99);
        // Notice: user is NOT added to sessionCohort

        $this->security->method('getUser')->willReturn($user);
        $this->requestStack->getSession()->set('active_cohort_id', 99);

        $this->cohortRepository->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn($sessionCohort);

        // It should fallback to the user's first cohort
        $this->assertSame($firstCohort, $this->cohortSession->getSelectedCohort());
    }

    public function testGetSelectedCohortReturnsNullIfNoCohortInSessionAndUserHasNoCohorts(): void
    {
        $user = new User();
        $this->security->method('getUser')->willReturn($user);

        // no session active_cohort_id

        $this->assertNull($this->cohortSession->getSelectedCohort());
    }

    public function testClearSelectedCohortRemovesFromSessionAndClearsCache(): void
    {
        $cohort = new Cohort();
        $this->setSharedEntityId($cohort, 42);

        $this->cohortSession->setSelectedCohort($cohort);
        $this->assertSame(42, $this->requestStack->getSession()->get('active_cohort_id'));
        $this->assertSame($cohort, $this->cohortSession->getSelectedCohort());

        $this->cohortSession->clearSelectedCohort();

        $this->assertFalse($this->requestStack->getSession()->has('active_cohort_id'));

        // Next call should re-fetch since cache is null
        $this->security->expects($this->once())->method('getUser')->willReturn(null);
        $this->assertNull($this->cohortSession->getSelectedCohort());
    }
    // Simulate ID injection
    private function setSharedEntityId($entity, int $id) {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
