<?php

namespace App\Tests\Controller;

use App\Entity\Assignment;
use App\Entity\AssignmentSubmission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AssignmentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->entityManager->createQuery('DELETE FROM App\Entity\PeerReviewScore')->execute();

        $this->entityManager->createQuery('DELETE FROM App\Entity\PeerReview')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\AssignmentSubmission')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Assignment')->execute();

        $conn = $this->entityManager->getConnection();
        // Removed PRAGMA
        $conn->executeStatement('DELETE FROM user');
        // Removed PRAGMA
    }

    private function createUser(string $email, string $password, array $roles = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('Test');
        $user->setLastname('User ' . uniqid());
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createAssignment(string $title): Assignment
    {
        $assignment = new Assignment();
        $assignment->setTitle($title);
        $assignment->setDescription('Test description');

        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        return $assignment;
    }

    private function createAssignmentSubmission(Assignment $assignment, User $user, string $content): AssignmentSubmission
    {
        $submission = new AssignmentSubmission();
        $submission->setAssignment($assignment);
        $submission->setUser($user);
        $submission->setContent($content);
        $submission->setStatus('submitted');

        $this->entityManager->persist($submission);
        $this->entityManager->flush();

        return $submission;
    }

    public function testSubmitReviewFailsWithoutCsrfToken(): void
    {
        $reviewer = $this->createUser('reviewer@example.com', 'password');
        $author = $this->createUser('author@example.com', 'password');
        $assignment = $this->createAssignment('Test Assignment');
        $submission = $this->createAssignmentSubmission($assignment, $author, 'My test submission');

        $this->client->loginUser($reviewer);

        // Send a POST request without a CSRF token
        $this->client->request('POST', $this->client->getContainer()->get('router')->generate('assignment_submit_review', ['id' => $assignment->getId(), 'submissionId' => $submission->getId()]), [
            'score' => 5,
            'feedback' => 'Good job!'
        ]);

        // Assert it redirects back to the review pool
        $this->assertResponseRedirects('/assignment/' . $assignment->getId() . '/review');
        $this->client->followRedirect();

        // Check for the error flash message
        $this->assertSelectorTextContains('.text-destructive', 'Invalid CSRF token.');
    }

    public function testSubmitReviewFailsWithInvalidCsrfToken(): void
    {
        $reviewer = $this->createUser('reviewer@example.com', 'password');
        $author = $this->createUser('author@example.com', 'password');
        $assignment = $this->createAssignment('Test Assignment');
        $submission = $this->createAssignmentSubmission($assignment, $author, 'My test submission');

        $this->client->loginUser($reviewer);

        // Send a POST request with an invalid CSRF token
        $this->client->request('POST', $this->client->getContainer()->get('router')->generate('assignment_submit_review', ['id' => $assignment->getId(), 'submissionId' => $submission->getId()]), [
            'score' => 5,
            'feedback' => 'Good job!',
            '_token' => 'invalid_csrf_token_value'
        ]);

        // Assert it redirects back to the review pool
        $this->assertResponseRedirects('/assignment/' . $assignment->getId() . '/review');
        $this->client->followRedirect();

        // Check for the error flash message
        $this->assertSelectorTextContains('.text-destructive', 'Invalid CSRF token.');
    }
}
