<?php

namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CommentSecurityTest extends WebTestCase
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
        $this->entityManager->createQuery('DELETE FROM App\Entity\Comment')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
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

    public function testEditAccessDeniedForAttacker(): void
    {
        $owner = $this->createUser('owner@example.com', 'password');
        $attacker = $this->createUser('attacker@example.com', 'password');
        $comment = $this->createComment($owner);

        $this->client->loginUser($attacker);
        $this->client->request('GET', '/comment/' . $comment->getId() . '/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditAccessGrantedForOwner(): void
    {
        $owner = $this->createUser('owner@example.com', 'password');
        $comment = $this->createComment($owner);

        $this->client->loginUser($owner);
        $this->client->request('GET', '/comment/' . $comment->getId() . '/edit');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testEditAccessGrantedForAdmin(): void
    {
        $owner = $this->createUser('owner@example.com', 'password');
        $admin = $this->createUser('admin@example.com', 'password', ['ROLE_ADMIN']);
        $comment = $this->createComment($owner);

        $this->client->loginUser($admin);
        $this->client->request('GET', '/comment/' . $comment->getId() . '/edit');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testDeleteAccessDeniedForAttacker(): void
    {
        $owner = $this->createUser('owner@example.com', 'password');
        $attacker = $this->createUser('attacker@example.com', 'password');
        $comment = $this->createComment($owner);

        $this->client->loginUser($attacker);

        // We cannot easily get CSRF token without access to form, but let's try with a fake one or just check if access control triggers before CSRF check?
        // Usually, IsGranted is checked before controller execution. CSRF is checked inside controller.
        // So we expect 403 regardless of CSRF token validity if the attribute works.

        $this->client->request('POST', '/comment/' . $comment->getId(), [
            '_token' => 'fake_token'
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
