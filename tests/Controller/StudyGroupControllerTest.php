<?php

namespace App\Tests\Controller;

use App\Entity\StudyGroup;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StudyGroupControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Clean up database for tests
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('PRAGMA foreign_keys = OFF');
        $conn->executeStatement('DELETE FROM study_group');
        $conn->executeStatement('DELETE FROM user');
        $conn->executeStatement('PRAGMA foreign_keys = ON');
    }

    public function testIndexRedirectsUnauthenticatedUser(): void
    {
        $this->client->request('GET', '/study-groups/');
        $this->assertResponseRedirects('/login');
    }

    public function testCreateRedirectsUnauthenticatedUser(): void
    {
        $this->client->request('POST', '/study-groups/create', [
            'name' => 'Test Group',
            'topic' => 'Test Topic',
            'maxMembers' => 5
        ]);
        $this->assertResponseRedirects('/login');
    }

    public function testIndexIsSuccessfulForAuthenticatedUser(): void
    {
        // Create test user
        $user = new User();
        $user->setEmail('test_study_group_index_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setFirstname('Test');
        $user->setLastname('StudyGroup');
        $user->setRoles(['ROLE_USER']);
        $user->setXp(100);

        $this->entityManager->persist($user);

        // Create test study group
        $studyGroup = new StudyGroup();
        $studyGroup->setName('Test Index Group');
        $studyGroup->setTopic('Test Index Topic');
        $studyGroup->setCreator($user);
        $studyGroup->setMaxMembers(15);
        $this->entityManager->persist($studyGroup);

        $this->entityManager->flush();

        // Simulate login
        $this->client->loginUser($user);

        // Access route
        $this->client->request('GET', '/study-groups/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'Test Index Group');
    }

    public function testCreateStudyGroup(): void
    {
        // Create test user
        $user = new User();
        $user->setEmail('test_study_group_create_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setFirstname('TestCreate');
        $user->setLastname('StudyGroupCreate');
        $user->setRoles(['ROLE_USER']);
        $user->setXp(100);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Simulate login
        $this->client->loginUser($user);

        // Access route
        $this->client->request('POST', '/study-groups/create', [
            'name' => 'New Test Group',
            'topic' => 'PHPUnit Testing',
            'maxMembers' => 20
        ]);

        // Check redirect
        $this->assertResponseRedirects('/study-groups/');

        // Follow redirect
        $this->client->followRedirect();

        // Assert flash message is displayed
        $this->assertSelectorExists('.text-success');

        // Check database for the new study group
        $studyGroupRepository = $this->entityManager->getRepository(StudyGroup::class);
        $studyGroup = $studyGroupRepository->findOneBy(['name' => 'New Test Group']);

        $this->assertNotNull($studyGroup);
        $this->assertEquals('New Test Group', $studyGroup->getName());
        $this->assertEquals('PHPUnit Testing', $studyGroup->getTopic());
        $this->assertEquals($user->getId(), $studyGroup->getCreator()->getId());
        $this->assertEquals(20, $studyGroup->getMaxMembers());
    }
}
