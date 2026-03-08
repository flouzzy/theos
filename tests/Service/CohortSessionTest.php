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
        $this->security->method('getUser')->willReturn(null);
        $this->assertNull($this->cohortSession->getSelectedCohort());
    }

    public function testGetSelectedCohortDefaultsToFirstUserCohort(): void
    {
        $user = new User();
        $cohort = new Cohort();
        $cohort->setName('Promo 1');
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
    
    // Simulate ID injection
    private function setSharedEntityId($entity, int $id) {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
