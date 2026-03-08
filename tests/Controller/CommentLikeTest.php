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
        $user->setEmail('liker@example.com');
        $user->setPassword('password');
        $user->setFirstname('Liker');
        $user->setLastname('User');
        $entityManager->persist($user);

        // Create Course/Module/Lesson/Comment
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setDescription('Test Description');
        $course->setSlug('test-course-like');
        $course->setAuthor($user);
        $entityManager->persist($course);

        $module = new Module();
        $module->setTitle('Test Module');
        $module->setSlug('test-module-like');
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
        // CreatedAt/UpdatedAt handled by LifecycleCallbacks
        $entityManager->persist($comment);

        $entityManager->flush();

        $client->loginUser($user);

        // 1. Try GET (Should fail with 405 Method Not Allowed)
        $client->request('GET', '/comment/' . $comment->getId() . '/like');
        $this->assertResponseStatusCodeSame(405);

        // 2. Try POST without CSRF (Should fail with 403 Forbidden)
        $client->request('POST', '/comment/' . $comment->getId() . '/like');
        $this->assertResponseStatusCodeSame(403);

        // 3. Try POST with CSRF (Should succeed)
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')->getToken('like_comment_' . $comment->getId());
        $client->request('POST', '/comment/' . $comment->getId() . '/like', [
            '_token' => $csrfToken->getValue(),
        ]);

        // Should redirect back
        $this->assertResponseRedirects();

        // Verify like was added
        $entityManager->clear(); // Clear cache to reload from DB
        $loadedComment = $entityManager->getRepository(Comment::class)->find($comment->getId());
        $this->assertTrue($loadedComment->getLikes()->contains($user));
    }
}
