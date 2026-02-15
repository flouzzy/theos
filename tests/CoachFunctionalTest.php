<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;

class CoachFunctionalTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCoachPageIsAccessibleAndContainsElements(): void
    {
        // Login as a test user
        $user = $this->client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        
        if (!$user) {
            $this->markTestSkipped('Test user not found.');
        }

        $this->client->loginUser($user);

        // Request the coach page
        $crawler = $this->client->request('GET', '/coach/');

        // Assert valid response
        $this->assertResponseIsSuccessful();

        // Check for key elements in the redesigned page
        $this->assertSelectorTextContains('h1', 'Ton Coach IA');
        $this->assertSelectorTextContains('p', 'Personnalisé pour ton apprentissage');
        
        // Check for Quick Actions
        $this->assertSelectorExists('.grid button:contains("Fixer un objectif")');
        $this->assertSelectorExists('.grid button:contains("Quiz du jour")');
        
        // Check for Sidebar elements
        $this->assertSelectorTextContains('h3', 'Chat avec ton coach');
    }
}
