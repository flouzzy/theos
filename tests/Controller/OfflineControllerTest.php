<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OfflineControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/offline');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Contenus Téléchargés');
    }
}
