<?php

namespace App\Tests\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LessonCompleteTest extends WebTestCase
{
    public function testMarkLessonAsCompleted(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Create a dummy user
        $user = new User();
        $user->setEmail('test_completion_' . uniqid() . '@example.com');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setPassword('password');

        // Create Course, Module, Lesson
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setSlug('test-course-' . uniqid());
        $course->setAuthor($user);
        $course->setDescription('Test description');

        $module = new Module();
        $module->setTitle('Test Module');
        $module->setSlug('test-module-' . uniqid());
        $module->addCourse($course);

        $lesson = new Lesson();
        $lesson->setTitle('Test Lesson');
        $lesson->setSlug('test-lesson-' . uniqid());
        $lesson->setModule($module);
        $lesson->setContent('Content');

        $em->persist($user);
        $em->persist($course);
        $em->persist($module);
        $em->persist($lesson);
        $em->flush();

        $client->loginUser($user);

        // We want to mark the lesson as completed, so we pass completed=1
        $client->request('GET', sprintf('/courses/%s/%s/lesson/%s/complete/1', $course->getSlug(), $module->getSlug(), $lesson->getId()));

        // Check redirection
        $this->assertResponseRedirects();

        // Check if DB was updated
        $completion = $em->getRepository(\App\Entity\Completion::class)->findOneBy([
            'user' => $user,
            'lesson' => $lesson
        ]);

        $this->assertNotNull($completion, 'A completion record should have been created');
        $this->assertTrue($completion->isCompleted(), 'The lesson should be marked as completed');
    }
}
