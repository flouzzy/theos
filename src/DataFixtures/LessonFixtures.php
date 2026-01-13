<?php

namespace App\DataFixtures;

use App\Entity\Lesson;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LessonFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private EntityManagerInterface $manager, private UserRepository $userRepository, private UserPasswordHasherInterface $passwordHasher)
    {
    }
    public function load(ObjectManager $manager): void
    {
        for ($index = 0; $index < 10; $index++) {
            $lesson = new Lesson;
            $lesson->setTitle('Lesson n°' . $index);
            $lesson->setDescription(AppFixtures::LOREM_IPSUM);
            $lesson->setContent(AppFixtures::LOREM_IPSUM);
            $lesson->setStatus('published');
            $lesson->setItemOrder($index);

            /**
             * @var \App\Entity\User $author
             */
            $author = $this->getReference(AppFixtures::SIMPLE_USER_REFERENCE . random_int(0, 9), \App\Entity\User::class);
            $lesson->setAuthor($author);

            // Add module
            $lesson->setModule($this->getReference(ModuleFixtures::MODULE_REFERENCE . random_int(0, 9), \App\Entity\Module::class));

            $this->manager->persist($lesson);
        }
        $this->manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ModuleFixtures::class,
        ];
    }
}
