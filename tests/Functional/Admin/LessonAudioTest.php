<?php

namespace App\Tests\Functional\Admin;

use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LessonAudioTest extends WebTestCase
{
    public function testGenerateAudioRouteDispatchesMessage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        
        $userRepository = $container->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'admin@test.com']);
        
        if (!$testUser) {
            $this->markTestSkipped('No admin user found for functional test.');
        }

        $client->loginUser($testUser);

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $lesson = $entityManager->getRepository(Lesson::class)->findOneBy(['status' => 'published']);
        
        if (!$lesson) {
            $this->markTestSkipped('No published lesson found for functional test.');
        }

        $client->request('POST', '/admin/lesson/' . $lesson->getId() . '/generate-audio');

        $this->assertResponseRedirects('/admin/lesson/' . $lesson->getId() . '/edit');
        
        $client->followRedirect();
        $this->assertSelectorTextContains('[role="alert"].text-success', 'La génération de l\'audio a été lancée');

        // Check if message was dispatched to the transport
        $this->assertCount(1, $container->get('messenger.transport.async')->get());
        }
        }

