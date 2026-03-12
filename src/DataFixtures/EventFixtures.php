<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\EventCategory;
use App\Entity\Cohort;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EventFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Event Categories
        $categories = ['Webinaire', 'Atelier', 'Conférence', 'Autre', 'Cours magistral', 'TD', 'TP', 'Présentiel'];
        $categoryObjects = [];
        foreach ($categories as $name) {
            $category = new EventCategory();
            $category->setName($name);
            $manager->persist($category);
            $categoryObjects[$name] = $category;
        }

        // Public Events
        $event1 = new Event();
        $event1->setTitle('Webinar: Best practices React');
        $event1->setStartAt(new \DateTimeImmutable('+2 days 14:00'));
        $event1->setEndAt(new \DateTimeImmutable('+2 days 16:00'));
        $event1->setType($categoryObjects['Webinaire']);
        $event1->setLocation('Visio');
        $manager->persist($event1);

        $event2 = new Event();
        $event2->setTitle('Session Q&A TypeScript');
        $event2->setStartAt(new \DateTimeImmutable('+5 days 16:00'));
        $event2->setEndAt(new \DateTimeImmutable('+5 days 17:30'));
        $event2->setType($categoryObjects['Webinaire']);
        $event2->setLocation('Teams');
        $manager->persist($event2);

        // Cohort Events
        $cohorts = $manager->getRepository(Cohort::class)->findAll();
        foreach ($cohorts as $cohort) {
            $event3 = new Event();
            $event3->setTitle('Live Coding: Symfony Forms');
            $event3->setStartAt(new \DateTimeImmutable('+1 week 10:00'));
            $event3->setEndAt(new \DateTimeImmutable('+1 week 12:00'));
            $event3->setCohort($cohort);
            $event3->setType($categoryObjects['Webinaire']);
            $event3->setLocation('Discord');
            $manager->persist($event3);
            
            $event4 = new Event();
            $event4->setTitle('Soutenance de projet');
            $event4->setStartAt(new \DateTimeImmutable('+2 weeks 09:00'));
            $event4->setEndAt(new \DateTimeImmutable('+2 weeks 17:00'));
            $event4->setCohort($cohort);
            $event4->setType($categoryObjects['Présentiel']);
            $event4->setLocation('Paris');
            $manager->persist($event4);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CohortFixtures::class,
        ];
    }
}
