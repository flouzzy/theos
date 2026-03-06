<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardTest extends WebTestCase
{
    public function testDashboardIsAccessible(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        // Find a user from fixtures (not admin if possible)
        $testUser = $userRepository->findOneBy([]);
        
        if (!$testUser) {
             $this->markTestSkipped('No user found in fixtures.');
        }

        $client->loginUser($testUser);
        $client->request('GET', '/cohort/'); 

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Mes Cours'); // Check availability in body
    }
}
