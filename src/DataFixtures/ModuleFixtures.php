<?php

namespace App\DataFixtures;

use App\Entity\Module;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ModuleFixtures extends Fixture implements DependentFixtureInterface
{
    public const MODULE_REFERENCE = 'module';
    public function __construct(private EntityManagerInterface $manager, private UserPasswordHasherInterface $passwordHasher)
    {
    }
    public function load(ObjectManager $manager): void
    {
        for ($index = 0; $index < 10; $index++) {
            $module = new Module;
            $module->setTitle('Module n°' . $index);
            $module->setDescription(AppFixtures::LOREM_IPSUM);
            $module->setStatus('published');
            $module->setItemOrder($index);

            /**
             * @var \App\Entity\User $author
             */
            $author = $this->getReference(AppFixtures::SIMPLE_USER_REFERENCE . random_int(0, 9));
            $module->setAuthor($author);

            // Link to random course
            $module->addCourse($this->getReference(CourseFixtures::COURSE_REFERENCE . random_int(0, 19)));

            $this->manager->persist($module);

            // Save ref for lessons
            $this->addReference(ModuleFixtures::MODULE_REFERENCE . $index, $module);
        }
        $this->manager->flush();
    }

    public function getDependencies()
    {
        return [
            CourseFixtures::class,
        ];
    }
}
