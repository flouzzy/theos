<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserDeletionTest extends WebTestCase
{
    public function testAdminCanDeleteUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $adminEmail = 'admin_'.uniqid().'@example.com';
        $userEmail = 'user_'.uniqid().'@example.com';

        // 1. Create an Admin user
        $admin = new User();
        $admin->setEmail($adminEmail);
        $admin->setPassword('password');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setFirstname('Admin');
        $admin->setLastname('Test');
        $entityManager->persist($admin);

        // 2. Create a User to be deleted
        $userToDelete = new User();
        $userToDelete->setEmail($userEmail);
        $userToDelete->setPassword('password');
        $userToDelete->setFirstname('User');
        $userToDelete->setLastname('ToDelete');
        $entityManager->persist($userToDelete);

        $entityManager->flush();

        // 3. Login as Admin
        $client->loginUser($admin);

        // 4. Go to show page where delete form exists
        $crawler = $client->request('GET', '/admin/user/'.$userToDelete->getId());
        $this->assertResponseIsSuccessful();

        // 5. Extract CSRF token from form
        $form = $crawler->filter('form[action$="/admin/user/'.$userToDelete->getId().'"]')->form();
        $csrfToken = $form->getValues()['_token'];

        // 6. Request deletion
        $client->request('POST', '/admin/user/'.$userToDelete->getId(), [
            '_token' => $csrfToken,
        ]);

        // 7. Assertions
        $this->assertResponseRedirects('/admin/user/');
        $client->followRedirect();
        $this->assertSelectorExists('[role="alert"]');
        // Check for common parts of the message to be translation-independent if possible, or just use the French one since it's the current environment
        $this->assertAnySelectorTextContains('[role="alert"]', 'été supprimé');

        // Check if user is gone from DB
        $deletedUser = $userRepository->findOneBy(['email' => $userEmail]);
        $this->assertNull($deletedUser);
    }

    public function testUserCanDeleteOwnAccount(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $userEmail = 'user_self_'.uniqid().'@example.com';

        // 1. Create a User
        $user = new User();
        $user->setEmail($userEmail);
        $user->setPassword('password');
        $user->setFirstname('Self');
        $user->setLastname('Delete');
        $entityManager->persist($user);
        $entityManager->flush();

        // 2. Login
        $client->loginUser($user);

        // 3. Request edit page where delete form exists
        $userId = $user->getId();
        $crawler = $client->request('GET', '/profile/edit');
        $this->assertResponseIsSuccessful();

        // 4. Extract CSRF token from delete form (action ends with /delete)
        $form = $crawler->filter('form[action*="/delete"]')->form();
        $csrfToken = $form->getValues()['_token'];

        // 5. Request deletion
        $client->request('POST', '/profile/'.$userId.'/delete', [
            '_token' => $csrfToken,
        ]);

        // 6. Assertions
        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('[role="alert"]');
        $this->assertAnySelectorTextContains('[role="alert"]', 'supprimé');

        // Check if user is gone from DB
        $deletedUser = $userRepository->findOneBy(['email' => $userEmail]);
        $this->assertNull($deletedUser);
    }
}
