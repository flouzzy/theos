<?php

namespace App\Tests\Functional\Admin;

use App\Entity\Course;
use App\Entity\Cohort;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EngagementAnalyticsTest extends WebTestCase
{
    public function testAnalyticsRoutes(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        
        $userRepository = $container->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['email' => 'charles@edounze.com']);
        
        $entityManager = $container->get('doctrine.orm.entity_manager');

        if (!$testUser) {
            $testUser = new User();
            $testUser->setEmail('charles@edounze.com');
            $testUser->setFirstname('Charles');
            $testUser->setLastname('Edounze');
            $testUser->setFullname('Charles Edounze');
            $testUser->setRoles(['ROLE_ADMIN']);
            $testUser->setPassword('test123');
            $testUser->setIsVerified(true);
            $entityManager->persist($testUser);
            $entityManager->flush();
        }

        $client->loginUser($testUser);

        // 1. Test Cohort Select
        $client->request('GET', '/admin/analytics/at-risk');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main h1', 'Analyses & Engagement');

        // 2. Test Course Select
        $client->request('GET', '/admin/analytics/content-efficacy');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main h1', 'Efficacité du Contenu');

        // 3. Test Content Efficacy for a specific course
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);
        if ($course) {
            $client->request('GET', '/admin/analytics/content-efficacy/' . $course->getId());
            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('main h1', 'Efficacité du Contenu');
        }
    }
}
