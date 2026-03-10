<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    public function testLoginAndGetCourses(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        // Find a test user or create one
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'student@example.com']);
        if (!$user) {
            $user = new User();
            $user->setEmail('student@example.com');
            $user->setFirstname('Student');
            $user->setLastname('Test');
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(
                self::getContainer()->get('security.user_password_hasher')->hashPassword($user, 'password')
            );
            $entityManager->persist($user);
            $entityManager->flush();
        }

        // 1. Test Login
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'student@example.com',
                'password' => 'password',
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $token = $data['token'];

        // 2. Test Get Courses
        $client->request(
            'GET',
            '/api/courses',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        $courses = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($courses);
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'wrong@example.com',
                'password' => 'wrong',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
