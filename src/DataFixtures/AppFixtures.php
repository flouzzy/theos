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
    public function __construct(private EntityManagerInterface $manager, private UserRepository $userRepository, private UserPasswordHasherInterface $passwordHasher)
    {
    }
    public function load(ObjectManager $manager): void
    {
        // Admin User
        $adminUser = $this->userRepository->findOneByUsername('admin@test.fr');
        if (!$adminUser) {
            $adminUser = $this->createUser([
                'email' => 'admin@test.fr',
                'firstname' => 'Charles',
                'lastname' => 'EDOU NZE',
                'fullname'
            ]);
            $this->manager->persist($adminUser);
        }

        // 5 user accounts
        for ($index = 0; $index < 4; $index++) {
            $userID = 'user' . $index;
            $newUser = $this->createUser([
                'email' => $userID . '@test.fr',
                'firstname' => $userID . ' FName',
                'lastname' => $userID . ' LName',
            ]);
            $this->manager->persist($newUser);
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
        if (count($role) > 0) {
            $user->setRoles(['ROLE_ADMIN']);
        }
        $user->setPassword($this->passwordHasher->hashPassword(
            $user,
            'test123'
        ));
        $user->setIsVerified(true);

        return $user;
    }
}
