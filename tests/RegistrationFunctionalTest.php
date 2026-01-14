<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationFunctionalTest extends WebTestCase
{
    public function testRegistration(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Inscription');

        // Find the form and fill it
        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[firstname]' => 'John',
            'registration_form[lastname]' => 'Doe',
            'registration_form[email]' => 'john.doe@test.com',
            'registration_form[plainPassword]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        $client->submit($form);

        // After successful registration we should be redirected
        $this->assertResponseRedirects();
        
        // Verify user creation in DB
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'john.doe@test.com']); // Use the hardcoded email for verification

        $this->assertNotNull($user);
        $this->assertSame('John', $user->getFirstname());
        $this->assertSame('Doe', $user->getLastname());
        // Verify fullname reconstruction logic
        $this->assertSame('Doe John', $user->getFullname());
    }
}
