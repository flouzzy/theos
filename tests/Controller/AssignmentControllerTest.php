<?php

namespace App\Tests\Controller;

use App\Entity\Assignment;
use App\Entity\AssignmentSubmission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AssignmentControllerTest extends WebTestCase
{
    public function testCannotReviewOwnSubmission(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Create a user
        $user = new User();
        $user->setEmail('test_assignment_' . uniqid() . '@example.com');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setPassword('password');

        // Create an assignment
        $assignment = new Assignment();
        $assignment->setTitle('Test Assignment');
        $assignment->setDescription('Test description');
        $assignment->setDueDate(new \DateTime('+1 week'));

        // Create an assignment submission by the same user
        $submission = new AssignmentSubmission();
        $submission->setAssignment($assignment);
        $submission->setUser($user);
        $submission->setContent('My submission content');
        $submission->setStatus('submitted');

        $em->persist($user);
        $em->persist($assignment);
        $em->persist($submission);
        $em->flush();

        $client->loginUser($user);

        // Accessing CSRF Token Manager to generate token
        $csrfTokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);
        $tokenId = 'submit_review' . $submission->getId();
        $token = $csrfTokenManager->getToken($tokenId)->getValue();

        // Perform a POST request to submit a review for own submission
        $client->request(
            'POST',
            sprintf('/assignment/%d/review/%d', $assignment->getId(), $submission->getId()),
            [
                'score' => 10,
                'feedback' => 'Good job, myself!',
                '_token' => $token
            ]
        );

        // Should redirect to assignment show
        $this->assertResponseRedirects('/assignment/' . $assignment->getId());

        $client->followRedirect();

        // Check if there is an error flash message saying you cannot review your own submission
        $this->assertSelectorTextContains('.text-destructive', 'You cannot review your own submission');

        // Ensure no review was created
        $reviewCount = $em->getRepository(\App\Entity\PeerReview::class)->count(['submission' => $submission]);
        $this->assertEquals(0, $reviewCount, 'A review should not have been created for own submission');
    }
}
