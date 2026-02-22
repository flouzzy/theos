<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminPaymentControllerTest extends WebTestCase
{
    public function testValidatePayment(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $container->get('doctrine');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        // Create a user
        $user = new User();
        $user->setEmail('test_payment_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setFullname('Test User');
        $user->setRoles(['ROLE_ADMIN']);

        $entityManager->persist($user);
        $entityManager->flush();

        // Login
        $client->loginUser($user);

        // Perform request
        $client->request('POST', '/admin/payment/payment/' . $user->getId());

        // Assert redirect
        $this->assertResponseRedirects('/admin/user/');

        // Assert payment status
        $entityManager->clear();
        $refreshedUser = $entityManager->getRepository(User::class)->find($user->getId());

        $this->assertNotNull($refreshedUser, 'User should exist.');
        $this->assertTrue($refreshedUser->isPaid(), 'User should be marked as paid.');
    }
}
