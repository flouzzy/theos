<?php

namespace App\Tests\Entity;

use App\Entity\Assignment;
use App\Entity\AssignmentSubmission;
use PHPUnit\Framework\TestCase;

class AssignmentTest extends TestCase
{
    public function testAddSubmission(): void
    {
        $assignment = new Assignment();
        $submission = new AssignmentSubmission();

        $assignment->addSubmission($submission);

        $this->assertCount(1, $assignment->getSubmissions(), 'Assignment should contain exactly 1 submission');
        $this->assertSame($assignment, $submission->getAssignment(), 'Submission should have its assignment set');
    }

    public function testAddSubmissionIdempotency(): void
    {
        $assignment = new Assignment();
        $submission = new AssignmentSubmission();

        $assignment->addSubmission($submission);
        $assignment->addSubmission($submission);

        $this->assertCount(1, $assignment->getSubmissions(), 'Assignment should contain only 1 submission despite adding it twice');
    }

    public function testRemoveSubmission(): void
    {
        $assignment = new Assignment();
        $submission = new AssignmentSubmission();

        $assignment->addSubmission($submission);
        $assignment->removeSubmission($submission);

        $this->assertCount(0, $assignment->getSubmissions(), 'Assignment should contain 0 submissions after removal');
        $this->assertNull($submission->getAssignment(), 'Submission should have its assignment unset');
    }
}
