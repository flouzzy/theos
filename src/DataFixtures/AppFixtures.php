<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const SIMPLE_USER_REFERENCE = 'user';
    public const LOREM_IPSUM = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum';

    public function __construct(private EntityManagerInterface $manager, private UserRepository $userRepository, private UserPasswordHasherInterface $passwordHasher)
    {
    }
    public function load(ObjectManager $manager): void
    {
        // Admin User
        $userAdmin = $this->userRepository->findOneByUsername('charles@charlesen.fr');
        if (!$userAdmin) {
            $userAdmin = $this->createUser(
                [
                    'email' => 'charles@charlesen.fr',
                    'firstname' => 'Charles',
                    'lastname' => 'EDOU NZE',
                    'fullname'
                ],
                ['ROLE_ADMIN']
            );
            $this->manager->persist($userAdmin);
        }

        $this->addReference(self::ADMIN_USER_REFERENCE, $userAdmin);

        // 5 user accounts
        $roles = ['ROLE_CREATOR', 'ROLE_CREATOR_ADMIN', 'ROLE_ADMIN'];
        for ($index = 0; $index < 10; $index++) {
            $userRef = self::SIMPLE_USER_REFERENCE . $index;
            $newUser = $this->createUser([
                'email' => $userRef . '@test.fr',
                'firstname' => $userRef . ' FName',
                'lastname' => $userRef . ' LName',
            ]);

            // Set random role to user (default : ROLE_USER)
            $randomeRoleIndex = random_int(0, 10);
            if (isset($roles[$randomeRoleIndex])) {
                $newUser->setRoles([$roles[$randomeRoleIndex]]);
            }

            $this->manager->persist($newUser);

            $this->addReference($userRef, $newUser);
        }

        $this->manager->flush();
    }

    private function createUser($userData, $role = [])
    {
        $user = new User();
        $user->setEmail($userData['email']);
        $user->setFirstname($userData['firstname']);
        $user->setLastname($userData['lastname']);
        $user->setFullname($userData['firstname'] . ' ' . $userData['lastname']);
        $user->setBio(AppFixtures::LOREM_IPSUM);
        if (count($role) > 0) {
            $user->setRoles($role);
        }
        $user->setPassword($this->passwordHasher->hashPassword(
            $user,
            'test123'
        ));
        $user->setIsVerified(true);

        return $user;
    }
}
