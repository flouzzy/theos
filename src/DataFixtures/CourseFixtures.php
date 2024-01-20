<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CourseFixtures extends Fixture
{
    public const COURSE_REFERENCE = 'course';
    public function __construct(private EntityManagerInterface $manager, private UserRepository $userRepository, private UserPasswordHasherInterface $passwordHasher)
    {
    }
    public function load(ObjectManager $manager): void
    {
        // Admin courses
        for ($index = 0; $index < 10; $index++) {
            # Create 5 courses
            $newCourse = $this->create('Course admin n°' . $index, $this->getReference(AppFixtures::ADMIN_USER_REFERENCE));
            $this->manager->persist($newCourse);

            $this->addReference(CourseFixtures::COURSE_REFERENCE . $index, $newCourse);
        }

        // Simple users course
        for ($index = 10; $index < 20; $index++) {
            # Create 5 courses
            $newCourse = $this->create('Course user n°' . $index, $this->getReference(AppFixtures::SIMPLE_USER_REFERENCE . random_int(0, 4)));
            $this->manager->persist($newCourse);

            $this->addReference(CourseFixtures::COURSE_REFERENCE . $index, $newCourse);
        }

        $this->manager->flush();
    }

    private function create($title, $author)
    {
        $course = new Course;
        $course->setTitle($title);
        $course->setDescription(AppFixtures::LOREM_IPSUM);
        $course->setAuthor($author);
        return $course;
    }
}
