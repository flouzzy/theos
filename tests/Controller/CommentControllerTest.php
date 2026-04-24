<?php

namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Entity\User;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CommentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->passwordHasher = static::getContainer()->get('security.user_password_hasher');

        // Clean up database
        $this->entityManager->getConnection()->executeStatement('PRAGMA foreign_keys = OFF');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Comment')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->getConnection()->executeStatement('PRAGMA foreign_keys = ON');
    }

    private function createUser(string $email, string $password, array $roles = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFullname('Test User ' . uniqid());
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createComment(User $owner, string $content = 'Test Comment'): Comment
    {
        $comment = new Comment();
        $comment->setUser($owner);
        $comment->setContent($content);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    public function testIndex(): void
    {
        $user = $this->createUser('test@example.com', 'password');
        $this->createComment($user, 'First comment');
        $this->createComment($user, 'Second comment');

        $this->client->request('GET', '/comment/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'First comment');
        $this->assertSelectorTextContains('body', 'Second comment');
    }

    public function testNew(): void
    {
        $user = $this->createUser('author@example.com', 'password');
        $this->client->loginUser($user); // Optional, if new requires login (though not restricted in code)

        $this->client->request('GET', '/comment/new');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Save', [
            'comment[content]' => 'A brand new comment',
        ]);

        $this->assertResponseRedirects('/comment/');

        $comment = $this->entityManager->getRepository(Comment::class)->findOneBy(['content' => 'A brand new comment']);
        $this->assertNotNull($comment);
    }

    public function testShow(): void
    {
        $user = $this->createUser('owner@example.com', 'password');
        $comment = $this->createComment($user, 'Show me this comment');

        $this->client->request('GET', '/comment/' . $comment->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Show me this comment');
    }

    public function testEdit(): void
    {
        $user = $this->createUser('owner@example.com', 'password');
        $comment = $this->createComment($user, 'Original content');

        $this->client->loginUser($user);
        $this->client->request('GET', '/comment/' . $comment->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Update', [
            'comment[content]' => 'Updated content',
        ]);

        $this->assertResponseRedirects('/comment/');

        $this->entityManager->clear();
        $updatedComment = $this->entityManager->getRepository(Comment::class)->find($comment->getId());
        $this->assertSame('Updated content', $updatedComment->getContent());
    }

    public function testDelete(): void
    {
        $user = $this->createUser('owner@example.com', 'password');
        $comment = $this->createComment($user, 'To be deleted');

        $this->client->loginUser($user);

        // We need to request the index page to get a valid CSRF token if it's rendered in a form,
        // or we can mock it. Let's just generate it since we have the container.
        $csrfToken = static::getContainer()->get('security.csrf.token_manager')->getToken('delete' . $comment->getId())->getValue();

        $this->client->request('POST', '/comment/' . $comment->getId(), [
            '_token' => $csrfToken
        ]);

        $this->assertResponseRedirects('/comment/');

        $this->entityManager->clear();
        $deletedComment = $this->entityManager->getRepository(Comment::class)->find($comment->getId());
        $this->assertNull($deletedComment);
    }

    public function testLikeRequiresLogin(): void
    {
        $user = $this->createUser('owner@example.com', 'password');
        $comment = $this->createComment($user, 'Like me');

        $this->client->request('POST', '/comment/' . $comment->getId() . '/like');
        $this->assertResponseRedirects('/login'); // Assuming route 'login' redirects correctly
    }

    public function testLikeAddsAndRemovesLike(): void
    {
        $owner = $this->createUser('owner@example.com', 'password');
        $liker = $this->createUser('liker@example.com', 'password');
        $comment = $this->createComment($owner, 'Like me');

        $this->client->loginUser($liker);

        $csrfToken = static::getContainer()->get('security.csrf.token_manager')->getToken('like_comment_' . $comment->getId())->getValue();

        // Add like
        $this->client->request('POST', '/comment/' . $comment->getId() . '/like', [
            '_token' => $csrfToken
        ]);

        $this->assertResponseRedirects('/comment/');

        $this->entityManager->clear();
        $likedComment = $this->entityManager->getRepository(Comment::class)->find($comment->getId());
        $this->assertCount(1, $likedComment->getLikes());

        // Remove like
        $this->client->request('POST', '/comment/' . $comment->getId() . '/like', [
            '_token' => $csrfToken
        ]);

        $this->assertResponseRedirects('/comment/');

        $this->entityManager->clear();
        $unlikedComment = $this->entityManager->getRepository(Comment::class)->find($comment->getId());
        $this->assertCount(0, $unlikedComment->getLikes());
    }
}
