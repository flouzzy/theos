<?php

namespace App\Test\Controller;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotificationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/admin/notification/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Notification::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Notification index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'notification[message]' => 'Testing',
            'notification[title]' => 'Testing',
            'notification[createdAt]' => 'Testing',
            'notification[sentAt]' => 'Testing',
            'notification[sendAt]' => 'Testing',
            'notification[isRead]' => 'Testing',
            'notification[user]' => 'Testing',
        ]);

        self::assertResponseRedirects('/sweet/food/');

        self::assertSame(1, $this->getRepository()->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Notification();
        $fixture->setMessage('My Title');
        $fixture->setTitle('My Title');
        $fixture->setCreatedAt('My Title');
        $fixture->setSentAt('My Title');
        $fixture->setSendAt('My Title');
        $fixture->setIsRead('My Title');
        $fixture->setUser('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Notification');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Notification();
        $fixture->setMessage('Value');
        $fixture->setTitle('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setSentAt('Value');
        $fixture->setSendAt('Value');
        $fixture->setIsRead('Value');
        $fixture->setUser('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'notification[message]' => 'Something New',
            'notification[title]' => 'Something New',
            'notification[createdAt]' => 'Something New',
            'notification[sentAt]' => 'Something New',
            'notification[sendAt]' => 'Something New',
            'notification[isRead]' => 'Something New',
            'notification[user]' => 'Something New',
        ]);

        self::assertResponseRedirects('/admin/notification/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getMessage());
        self::assertSame('Something New', $fixture[0]->getTitle());
        self::assertSame('Something New', $fixture[0]->getCreatedAt());
        self::assertSame('Something New', $fixture[0]->getSentAt());
        self::assertSame('Something New', $fixture[0]->getSendAt());
        self::assertSame('Something New', $fixture[0]->getIsRead());
        self::assertSame('Something New', $fixture[0]->getUser());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Notification();
        $fixture->setMessage('Value');
        $fixture->setTitle('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setSentAt('Value');
        $fixture->setSendAt('Value');
        $fixture->setIsRead('Value');
        $fixture->setUser('Value');

        $this->manager->remove($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/admin/notification/');
        self::assertSame(0, $this->repository->count([]));
    }
}
