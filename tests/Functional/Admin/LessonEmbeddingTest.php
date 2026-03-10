<?php

namespace App\Tests\Functional\Admin;

use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LessonEmbeddingTest extends WebTestCase
{
    public function testGenerateEmbeddingRouteDispatchesMessage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        
        $userRepository = $container->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'charles@edounze.com']);
        
        $entityManager = $container->get('doctrine.orm.entity_manager');

        if (!$testUser) {
            $testUser = new User();
            $testUser->setEmail('charles@edounze.com');
            $testUser->setFirstname('Charles');
            $testUser->setLastname('Edounze');
            $testUser->setFullname('Charles Edounze');
            $testUser->setRoles(['ROLE_ADMIN']);
            $testUser->setPassword('test123');
            $testUser->setIsVerified(true);
            $entityManager->persist($testUser);
            $entityManager->flush();
        }

        $client->loginUser($testUser);
        
        // Create a course, module and lesson if none exists
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setSlug('test-course');
        $course->setAuthor($testUser);
        $entityManager->persist($course);

        $module = new Module();
        $module->setTitle('Test Module');
        $module->setSlug('test-module');
        $module->addCourse($course);
        $module->setAuthor($testUser);
        $entityManager->persist($module);

        $lesson = new Lesson();
        $lesson->setTitle('Test Lesson');
        $lesson->setSlug('test-lesson');
        $lesson->setModule($module);
        $lesson->setContent('Test Content');
        $lesson->setStatus('published');
        $lesson->setAuthor($testUser);
        $entityManager->persist($lesson);
        
        $entityManager->flush();

        $client->request('POST', '/admin/lesson/' . $lesson->getId() . '/generate-embedding');

        $this->assertResponseRedirects('/admin/lesson/' . $lesson->getId() . '/edit');
        
        $client->followRedirect();
        $this->assertSelectorTextContains('[role="alert"]', 'La génération de l\'embedding a été lancée');

        // Check if message was dispatched to the transport

        $this->assertGreaterThan(0, count($container->get('messenger.transport.async')->get()));
    }
}
