<?php

namespace App\DataFixtures;

use App\Entity\Cohort;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CohortFixtures extends Fixture implements DependentFixtureInterface
{
    public const COHORT_REFERENCE = 'cohort';

    public function load(ObjectManager $manager): void
    {
        // Create 3 cohorts with different statuses
        $cohortsData = [
            [
                'title' => 'Promotion 2026 - Web Development',
                'slug' => 'promo-2026-web-dev',
                'startAt' => new \DateTimeImmutable('+7 days'),
                'capacity' => 30,
                'status' => 'published',
            ],
            [
                'title' => 'Promotion 2026 - Data Science',
                'slug' => 'promo-2026-data-science',
                'startAt' => new \DateTimeImmutable('+14 days'),
                'capacity' => 25,
                'status' => 'published',
            ],
            [
                'title' => 'Promotion 2026 - Mobile Development',
                'slug' => 'promo-2026-mobile-dev',
                'startAt' => new \DateTimeImmutable('+21 days'),
                'capacity' => 20,
                'status' => 'draft',
            ],
        ];

        foreach ($cohortsData as $index => $data) {
            $cohort = new Cohort();
            $cohort->setTitle($data['title']);
            $cohort->setSlug($data['slug']);
            $cohort->setStartAt($data['startAt']);
            $cohort->setCapacity($data['capacity']);
            $cohort->setStatus($data['status']);

            // Add some courses to cohort
            for ($i = 0; $i < 3; $i++) {
                $course = $this->getReference(CourseFixtures::COURSE_REFERENCE . $i, \App\Entity\Course::class);
                $cohort->addCourse($course);
            }

            $manager->persist($cohort);
            $this->addReference(self::COHORT_REFERENCE . $index, $cohort);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CourseFixtures::class,
        ];
    }
}
