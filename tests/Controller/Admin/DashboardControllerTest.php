<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects('/login');
    }

    public function testIndexRequiresAdminRole(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $container->get('doctrine');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $user = new User();
        $user->setEmail('test_user_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setFullname('Test Regular User');
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexAsAdmin(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $container->get('doctrine');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $admin = new User();
        $admin->setEmail('test_admin_' . uniqid() . '@example.com');
        $admin->setPassword('password');
        $admin->setFullname('Test Admin User');
        $admin->setRoles(['ROLE_ADMIN']);

        $entityManager->persist($admin);
        $entityManager->flush();

        $client->loginUser($admin);
        $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
    }
}
