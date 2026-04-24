<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OfflineControllerTest extends WebTestCase
{
    public function testIndexIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/offline');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Contenus Téléchargés');
    }
}
