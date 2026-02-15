<?php

namespace App\Tests\Controller;

use App\Entity\Completion;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LessonFrontendTest extends WebTestCase
{
    public function testShowLessonWithCompletion()
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Create User
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $entityManager->persist($user);

        // Create Course
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setDescription('Test Description');
        $course->setAuthor($user);
        $entityManager->persist($course);

        // Create Module
        $module = new Module();
        $module->setTitle('Test Module');
        $course->addModule($module); // Use addModule on course to ensure bidirectional link
        $entityManager->persist($module);

        // Create Lesson
        $lesson = new Lesson();
        $lesson->setTitle('Test Lesson');
        $module->addLesson($lesson); // Explicitly add to collection
        $lesson->setContent('Content');
        $entityManager->persist($lesson);

        // Create Completion
        $completion = new Completion();
        $completion->setUser($user);
        $completion->setLesson($lesson);
        $completion->setCompleted(true);
        $entityManager->persist($completion);

        // Create another course and completion to test filtering
        $otherCourse = new Course();
        $otherCourse->setTitle('Other Course');
        $otherCourse->setDescription('Other Description');
        $otherCourse->setAuthor($user);
        $entityManager->persist($otherCourse);

        $otherModule = new Module();
        $otherModule->setTitle('Other Module');
        $otherModule->addCourse($otherCourse);
        $entityManager->persist($otherModule);

        $otherLesson = new Lesson();
        $otherLesson->setTitle('Other Lesson');
        $otherLesson->setModule($otherModule);
        $otherLesson->setContent('Other Content');
        $entityManager->persist($otherLesson);

        $otherCompletion = new Completion();
        $otherCompletion->setUser($user);
        $otherCompletion->setLesson($otherLesson);
        $otherCompletion->setCompleted(true);
        $entityManager->persist($otherCompletion);


        $entityManager->flush();

        // Login
        $client->loginUser($user);

        // Request Lesson Page
        // URL: /courses/{courseSlug}/{moduleSlug}/lesson/{id}
        $url = sprintf('/courses/%s/%s/lesson/%d', $course->getSlug(), $module->getSlug(), $lesson->getId());
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        // Check if completion indicator is present (the SVG checkmark)
        $this->assertSelectorExists('path[d="M5 13l4 4L19 7"]');

        // Ensure "Other Lesson" is not shown (it shouldn't be because it's in another course)
        $this->assertSelectorTextNotContains('body', 'Other Lesson');
    }
}
