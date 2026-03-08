<?php

namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\Course;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommentLikeTest extends WebTestCase
{
    public function testLikeCommentSecurity()
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Create User
        $user = new User();
        $user->setEmail('liker-' . bin2hex(random_bytes(4)) . '@example.com');
        $user->setPassword('password');
        $user->setFirstname('Liker');
        $user->setLastname('User');
        $entityManager->persist($user);

        // Create Course/Module/Lesson/Comment
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setDescription('Test Description');
        $course->setSlug('test-course-like-' . bin2hex(random_bytes(4)));
        $course->setAuthor($user);
        $entityManager->persist($course);

        $module = new Module();
        $module->setTitle('Test Module');
        $module->setSlug('test-module-like-' . bin2hex(random_bytes(4)));
        $module->addCourse($course);
        $entityManager->persist($module);

        $lesson = new Lesson();
        $lesson->setTitle('Test Lesson');
        $lesson->setModule($module);
        $lesson->setContent('Content');
        $entityManager->persist($lesson);

        $comment = new Comment();
        $comment->setContent('Test Comment');
        $comment->setUser($user);
        $comment->setLesson($lesson);
        $entityManager->persist($comment);

        $entityManager->flush();

        // 1. Try GET (Should fail with 405 Method Not Allowed)
        $client->loginUser($user);
        $client->request('GET', '/comment/' . $comment->getId() . '/like');
        $this->assertResponseStatusCodeSame(405);

        // 2. Try POST without CSRF (Should fail with 403 Forbidden)
        $client->request('POST', '/comment/' . $comment->getId() . '/like');
        $this->assertResponseStatusCodeSame(403);

        // 3. Try POST with CSRF via UI (Should succeed)
        $url = sprintf('/courses/%s/%s/lesson/%d', $course->getSlug(), $module->getSlug(), $lesson->getId());
        $crawler = $client->request('GET', $url);
        
        $this->assertResponseIsSuccessful();
        
        // Find the like form for this specific comment
        $formAction = '/comment/' . $comment->getId() . '/like';
        $form = $crawler->filter('form[action="' . $formAction . '"]')->form();
        $client->submit($form);

        // Should redirect back
        $this->assertResponseRedirects();

        // Verify like was added
        $entityManager->clear(); 
        $loadedComment = $entityManager->getRepository(Comment::class)->find($comment->getId());
        $this->assertCount(1, $loadedComment->getLikes());
        $this->assertEquals($user->getId(), $loadedComment->getLikes()->first()->getId());
    }
}

