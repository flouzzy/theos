<?php

namespace App\Test\Controller;

use App\Entity\Calendar;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CalendarControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/admin/calendar/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Calendar::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Calendar index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'calendar[description]' => 'Testing',
            'calendar[url]' => 'Testing',
            'calendar[embed]' => 'Testing',
            'calendar[cohort]' => 'Testing',
        ]);

        self::assertResponseRedirects('/sweet/food/');

        self::assertSame(1, $this->getRepository()->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Calendar();
        $fixture->setDescription('My Title');
        $fixture->setUrl('My Title');
        $fixture->setEmbed('My Title');
        $fixture->setCohort('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Calendar');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Calendar();
        $fixture->setDescription('Value');
        $fixture->setUrl('Value');
        $fixture->setEmbed('Value');
        $fixture->setCohort('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'calendar[description]' => 'Something New',
            'calendar[url]' => 'Something New',
            'calendar[embed]' => 'Something New',
            'calendar[cohort]' => 'Something New',
        ]);

        self::assertResponseRedirects('/admin/calendar/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getUrl());
        self::assertSame('Something New', $fixture[0]->getEmbed());
        self::assertSame('Something New', $fixture[0]->getCohort());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Calendar();
        $fixture->setDescription('Value');
        $fixture->setUrl('Value');
        $fixture->setEmbed('Value');
        $fixture->setCohort('Value');

        $this->manager->remove($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/admin/calendar/');
        self::assertSame(0, $this->repository->count([]));
    }
}
