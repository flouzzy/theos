<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use App\Entity\TriviaQuestion;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TriviaControllerTest extends WebTestCase
{
    public function testCheckTriviaWithoutCsrf(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        // Find or create test user
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'student@example.com']);
        if (!$user) {
            $user = new User();
            $user->setEmail('student@example.com');
            $user->setFirstname('Student');
            $user->setLastname('Test');
            $user->setFullname('Student Test');
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(
                self::getContainer()->get('security.user_password_hasher')->hashPassword($user, 'password')
            );
            $entityManager->persist($user);
        }

        // Create a trivia question
        $q = new TriviaQuestion();
        $q->setQuestion('What is 2+2?');
        $q->setOptions(['3', '4', '5']);
        $q->setCorrectAnswer('4');
        $entityManager->persist($q);
        $entityManager->flush();

        $client->loginUser($user);

        // Test without CSRF token - should fail with 403
        $client->request(
            'POST',
            '/api/trivia/check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'id' => $q->getId(),
                'answer' => '4',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid CSRF token', $data['error']);
    }

    public function testCheckTriviaWithInvalidCsrf(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'student@example.com']);
        $q = $entityManager->getRepository(TriviaQuestion::class)->findOneBy(['question' => 'What is 2+2?']);

        $client->loginUser($user);

        // Test with invalid CSRF token - should fail with 403
        $client->request(
            'POST',
            '/api/trivia/check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'id' => $q->getId(),
                'answer' => '4',
                '_token' => 'invalid_token'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid CSRF token', $data['error']);
    }
}
