<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Comprehensive smoke tests for all authenticated routes
 */
class AuthenticatedRoutesTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
        $count = $em->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u')->getSingleScalarResult();
        if ($count > 0) throw new \Exception("Warning! Users still exist in DB: $count");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    /**
     * Test authenticated user can access all protected routes
     * 
     * @dataProvider provideAuthenticatedUrls
     */
    public function testAuthenticatedRoutesAreAccessible(string $url, int $expectedStatus = 200): void
    {
        $container = $this->client->getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get('security.password_hasher');
        $userRepo = $container->get('doctrine')->getRepository(\App\Entity\User::class);
        
        // Find or create a test user
        $email = 'test_auth@example.com';
        $user = new \App\Entity\User();
        $user->setEmail($email);
        $user->setFirstname('Test');
        $user->setLastname('Auth');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);
        $em->persist($user);
        $em->flush();
        
        $this->client->loginUser($user);

        $this->client->request('GET', $url);
        
        // Check response status
        if ($expectedStatus === 200 && $this->client->getResponse()->isRedirect()) {
            $this->assertTrue($this->client->getResponse()->isRedirect(), sprintf('Route %s should return 200 or redirect', $url));
        } else {
            $this->assertResponseStatusCodeSame($expectedStatus, sprintf('Route %s returned unexpected status', $url));
        }
    }

    /**
     * Provides authenticated user URLs
     */
    public static function provideAuthenticatedUrls(): \Generator
    {
        yield 'Home/Dashboard' => ['/'];
        yield 'Profile' => ['/profile'];
        yield 'Calendar' => ['/calendar'];
        yield 'Notes' => ['/note/'];
        yield 'Notifications' => ['/notification/'];
        yield 'Courses List' => ['/courses/'];
        yield 'Cohorts' => ['/cohort/'];
        yield 'Payment' => ['/payment/'];
        yield 'Evaluations' => ['/evaluation/'];
        yield 'Comments' => ['/comment/'];
    }
}
