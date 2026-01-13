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
        $this->assertSelectorTextContains('h1', 'Nouveau compte');

        // Generate unique email
        $email = 'test_' . uniqid() . '@example.com';
        
        $form = $crawler->filter('button[type="submit"]')->form();
        $form['registration_form[firstname]'] = 'John';
        $form['registration_form[lastname]'] = 'Doe';
        $form['registration_form[email]'] = $email;
        $form['registration_form[plainPassword]'] = 'password123';
        $form['registration_form[agreeTerms]'] = true;

        $client->submit($form);

        // Should redirect after successful registration (usually to home or login or email verification)
        // Assuming redirection to home or login with flash message
        $this->assertResponseRedirects();
        
        // Verify user creation in DB
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        $this->assertNotNull($user);
        $this->assertSame('John', $user->getFirstname());
        $this->assertSame('Doe', $user->getLastname());
        // Verify fullname reconstruction logic
        $this->assertSame('Doe John', $user->getFullname());
    }
}
