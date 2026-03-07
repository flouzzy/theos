<?php

namespace App\Tests\Functional;

use App\Entity\Cohort;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CohortInvitationTest extends WebTestCase
{
    public function testInvitationFlow(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // 1. Create a Cohort with a token
        $token = bin2hex(random_bytes(16));
        $cohort = new Cohort();
        $cohort->setTitle('Test Cohort Invitation');
        $cohort->setYear(2026);
        $cohort->setStatus('published');
        $cohort->setInvitationToken($token);
        $em->persist($cohort);
        $em->flush();

        // 2. Access the join link
        $client->request('GET', '/join/' . $token);
        
        // Should redirect to register
        $this->assertResponseRedirects('/register');
        $this->assertTrue($client->getRequest()->getSession()->has('pending_cohort_token'));
        $this->assertEquals($token, $client->getRequest()->getSession()->get('pending_cohort_token'));

        // 3. Register a new user
        $email = 'invited-' . bin2hex(random_bytes(4)) . '@example.com';
        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Créer mon compte')->form([
            'registration_form[firstname]' => 'Test',
            'registration_form[lastname]' => 'Invited',
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);
        $client->submit($form);

        // Should redirect to cohort_complete_join (via RegistrationController update)
        $this->assertResponseRedirects('/invitation/complete');
        $client->followRedirect();

        // 4. Verify user is in cohort
        $this->assertResponseRedirects('/'); // cohort_complete_join redirects to home
        
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($user);
        
        // Refresh cohort
        $cohort = $em->getRepository(Cohort::class)->find($cohort->getId());
        $this->assertTrue($cohort->getUsers()->contains($user), 'User should be added to the cohort after registration');
    }
}
