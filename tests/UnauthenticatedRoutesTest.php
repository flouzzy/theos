<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UnauthenticatedRoutesTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    /**
     * Test unauthenticated users are redirected to login
     * 
     * @dataProvider provideProtectedUrls
     */
    public function testUnauthenticatedUsersRedirectedToLogin(string $url): void
    {
        // Nuke the session from orbit
        $this->client->getCookieJar()->clear();
        
        $this->client->request('GET', $url);
        
        // Should redirect to login
        $this->assertResponseRedirects('http://localhost/login', null, sprintf('Route %s should redirect unauthenticated users to login', $url));
    }

    public static function provideProtectedUrls(): \Generator
    {
        yield 'Profile' => ['/profile'];
        yield 'Calendar' => ['/calendar'];
        yield 'Notes' => ['/note/'];
        yield 'Notifications' => ['/notification/'];
        yield 'Cohorts' => ['/cohort/'];
        yield 'Payment' => ['/payment/'];
    }
}
