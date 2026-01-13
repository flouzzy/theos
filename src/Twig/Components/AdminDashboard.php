<?php

namespace App\Twig\Components;

use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Repository\ModuleCompletionRepository;
use App\Repository\UserRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('AdminDashboard')]
class AdminDashboard
{
    use DefaultActionTrait;

    public function __construct(
        private UserRepository $userRepository,
        private ModuleCompletionRepository $moduleCompletionRepository,
        private CompletionRepository $completionRepository,
        private CourseCompletionRepository $courseCompletionRepository
    ) {
    }

    public function getUsersTotal(): int
    {
        return $this->userRepository->countVerifiedUsers();
    }

    public function getCoursesTotal(): int
    {
        return $this->courseCompletionRepository->countCoursesCompletions();
    }

    public function getModulesTotal(): int
    {
        return $this->moduleCompletionRepository->countModuleCompletions();
    }

    public function getLessonsTotal(): int
    {
        return $this->completionRepository->countLessonsCompletions();
    }
}
