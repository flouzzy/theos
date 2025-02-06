<?php

namespace App\Test\Controller;

use App\Entity\Cohort;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CohortControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/admin/cohort/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Cohort::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Cohort index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'cohort[title]' => 'Testing',
            'cohort[description]' => 'Testing',
            'cohort[image]' => 'Testing',
            'cohort[year]' => 'Testing',
            'cohort[status]' => 'Testing',
            'cohort[createdAt]' => 'Testing',
            'cohort[updatedAt]' => 'Testing',
            'cohort[courses]' => 'Testing',
            'cohort[users]' => 'Testing',
        ]);

        self::assertResponseRedirects('/sweet/food/');

        self::assertSame(1, $this->getRepository()->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Cohort();
        $fixture->setTitle('My Title');
        $fixture->setDescription('My Title');
        $fixture->setImage('My Title');
        $fixture->setYear('My Title');
        $fixture->setStatus('My Title');
        $fixture->setCreatedAt('My Title');
        $fixture->setUpdatedAt('My Title');
        $fixture->setCourses('My Title');
        $fixture->setUsers('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Cohort');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Cohort();
        $fixture->setTitle('Value');
        $fixture->setDescription('Value');
        $fixture->setImage('Value');
        $fixture->setYear('Value');
        $fixture->setStatus('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setUpdatedAt('Value');
        $fixture->setCourses('Value');
        $fixture->setUsers('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'cohort[title]' => 'Something New',
            'cohort[description]' => 'Something New',
            'cohort[image]' => 'Something New',
            'cohort[year]' => 'Something New',
            'cohort[status]' => 'Something New',
            'cohort[createdAt]' => 'Something New',
            'cohort[updatedAt]' => 'Something New',
            'cohort[courses]' => 'Something New',
            'cohort[users]' => 'Something New',
        ]);

        self::assertResponseRedirects('/admin/cohort/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getTitle());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getImage());
        self::assertSame('Something New', $fixture[0]->getYear());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getCreatedAt());
        self::assertSame('Something New', $fixture[0]->getUpdatedAt());
        self::assertSame('Something New', $fixture[0]->getCourses());
        self::assertSame('Something New', $fixture[0]->getUsers());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Cohort();
        $fixture->setTitle('Value');
        $fixture->setDescription('Value');
        $fixture->setImage('Value');
        $fixture->setYear('Value');
        $fixture->setStatus('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setUpdatedAt('Value');
        $fixture->setCourses('Value');
        $fixture->setUsers('Value');

        $this->manager->remove($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/admin/cohort/');
        self::assertSame(0, $this->repository->count([]));
    }
}
