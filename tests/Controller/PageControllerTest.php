<?php

namespace App\Tests\Controller;

use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PageControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        // Nettoyer la base de données
        $page = $this->entityManager->getRepository(Page::class)->findOneBy(['slug' => 'test-page-slug']);
        if ($page) {
            $this->entityManager->remove($page);
            $this->entityManager->flush();
        }

        $this->entityManager->close();
        parent::tearDown();
    }

    public function testShowPageIsSuccessful(): void
    {
        // Créer une page de test
        $page = new Page();
        $page->setTitle('Test Page Title');
        $page->setContent('<p>Test Page Content</p>');
        $page->setSlug('test-page-slug');

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        // Accéder à la route
        $this->client->request('GET', '/test-page-slug');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Test Page Title');
        $this->assertSelectorTextContains('.prose p', 'Test Page Content');
    }

    public function testShowPageNotFound(): void
    {
        $this->client->request('GET', '/non-existent-page-slug');
        $this->assertResponseStatusCodeSame(404);
    }
}
