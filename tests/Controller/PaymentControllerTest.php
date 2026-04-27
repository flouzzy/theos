<?php

namespace App\Tests\Controller;

use App\Entity\PaymentSetting;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
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
        $this->entityManager->close();
        parent::tearDown();
    }

    public function testIndexIsSuccessfulForAuthenticatedUser(): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test_payment_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setFirstname('Test');
        $user->setLastname('Payment');
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);

        // Nettoyer d'abord les anciens paramètres de paiement s'il y en a pour éviter les conflits
        $existingPayments = $this->entityManager->getRepository(PaymentSetting::class)->findAll();
        foreach ($existingPayments as $existingPayment) {
            $this->entityManager->remove($existingPayment);
        }

        // Créer un paramètre de paiement
        $paymentSetting = new PaymentSetting();
        $paymentSetting->setPricing(99);
        $paymentSetting->setRib('FR7600000000000000000000000');
        $paymentSetting->setNote('Test Payment Note');

        $this->entityManager->persist($paymentSetting);
        $this->entityManager->flush();

        // Simuler la connexion
        $this->client->loginUser($user);

        // Accéder à la route
        $this->client->request('GET', '/payment/');

        $this->assertResponseIsSuccessful();

        // Assert content
        $this->assertSelectorTextContains('ion-card-title', '99');
        $this->assertSelectorTextContains('p', 'FR7600000000000000000000000');

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->remove($paymentSetting);
        $this->entityManager->flush();
    }

    public function testIndexRedirectsUnauthenticatedUser(): void
    {
        $this->client->request('GET', '/payment/');
        $this->assertResponseRedirects('/login');
    }
}
