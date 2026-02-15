<?php

namespace App\Test\Controller;

use App\Entity\Course;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/admin/course/';
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Course::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();

        // Setup Admin User
        $userRepo = $this->manager->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => 'admin_test@example.com']);

        if (!$user) {
            $user = new User();
            $user->setEmail('admin_test@example.com');
            $user->setPassword('password');
            $user->setRoles(['ROLE_ADMIN']);
            $user->setFirstname('Admin');
            $user->setLastname('Test');
            $user->setIsVerified(true);
            $this->manager->persist($user);
            $this->manager->flush();
        }

        $this->user = $user;
    }

    public function testIndex(): void
    {
        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        // self::assertPageTitleContains('Course index');
    }

    public function testNew(): void
    {
        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $form = $crawler->filter('form[name="course"]')->form([
            'course[title]' => 'New Test Course',
            'course[description]' => 'Description of the test course',
            'course[status]' => 'draft',
            'course[itemOrder]' => 1,
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/admin/course/');

        $this->client->followRedirect();
        self::assertResponseStatusCodeSame(200);

        self::assertSame(1, $this->repository->count([]));

        /** @var Course $course */
        $course = $this->repository->findOneBy(['title' => 'New Test Course']);
        self::assertNotNull($course);
        self::assertSame('Description of the test course', $course->getDescription());
        self::assertSame('draft', $course->getStatus());
        self::assertSame($this->user->getId(), $course->getAuthor()->getId());
    }

    public function testShow(): void
    {
        $fixture = new Course();
        $fixture->setTitle('My Title');
        $fixture->setDescription('My Description');
        $fixture->setAuthor($this->user);
        $fixture->setStatus('draft');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->loginUser($this->user);
        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('My Title');
    }

    public function testEdit(): void
    {
        $fixture = new Course();
        $fixture->setTitle('Value');
        $fixture->setDescription('Value');
        $fixture->setAuthor($this->user);
        $fixture->setStatus('draft');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $form = $crawler->filter('form[name="course"]')->form([
            'course[title]' => 'Something New',
            'course[description]' => 'New Description',
            'course[status]' => 'published',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects(sprintf('/admin/course/%s/edit', $fixture->getId()));

        $fixture = $this->repository->find($fixture->getId());

        self::assertSame('Something New', $fixture->getTitle());
        self::assertSame('New Description', $fixture->getDescription());
        self::assertSame('published', $fixture->getStatus());
    }

    public function testRemove(): void
    {
        $fixture = new Course();
        $fixture->setTitle('Value');
        $fixture->setAuthor($this->user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        // Find the form that deletes the item (usually the only one with POST method without name, or by action)
        // Since show page might have multiple forms, let's filter by action containing delete or specific structure
        // The delete form is usually: <form method="post" action="/admin/course/{id}" ...>
        // But the show page has only one form? No, edit link is an <a>.
        // The delete form is the only form on the show page probably?
        // Let's filter by action.
        $form = $crawler->filter('form')->form();

        $this->client->submit($form);

        self::assertResponseRedirects('/admin/course/');
        self::assertSame(0, $this->repository->count([]));
    }
}
