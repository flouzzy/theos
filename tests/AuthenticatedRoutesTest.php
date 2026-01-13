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
    }

    /**
     * Test authenticated user can access all protected routes
     * 
     * @dataProvider provideAuthenticatedUrls
     */
    public function testAuthenticatedRoutesAreAccessible(string $url, int $expectedStatus = 200): void
    {
        // Login as a test user
        $user = $this->client->getContainer()->get('doctrine')->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'admin@example.com']);
        
        if ($user) {
            $this->client->loginUser($user);
        }

        $this->client->request('GET', $url);
        
        // Check response status
        $this->assertResponseStatusCodeSame($expectedStatus, sprintf('Route %s returned unexpected status', $url));
    }

    /**
     * Test unauthenticated users are redirected to login
     * 
     * @dataProvider provideProtectedUrls
     */
    public function testUnauthenticatedUsersRedirectedToLogin(string $url): void
    {
        $this->client->request('GET', $url);
        
        // Should redirect to login
        $this->assertResponseRedirects('/login', null, sprintf('Route %s should redirect unauthenticated users to login', $url));
    }

    /**
     * Provides authenticated user URLs
     */
    public static function provideAuthenticatedUrls(): \Generator
    {
        yield 'Home/Dashboard' => ['/'];
        yield 'Profile' => ['/profile'];
        yield 'Calendar' => ['/calendar'];
        yield 'Notes' => ['/note'];
        yield 'Notifications' => ['/notification/'];
        yield 'Courses List' => ['/courses/'];
        yield 'Cohorts' => ['/cohort/'];
        yield 'Payment' => ['/payment/'];
    }

    /**
     * Provides protected URLs that require authentication
     */
    public static function provideProtectedUrls(): \Generator
    {
        yield 'Profile' => ['/profile'];
        yield 'Calendar' => ['/calendar'];
        yield 'Notes' => ['/note'];
        yield 'Notifications' => ['/notification/'];
        yield 'Cohorts' => ['/cohort/'];
        yield 'Payment' => ['/payment/'];
    }
}
