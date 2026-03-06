<?php

namespace App\Twig\Components;

use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Repository\ModuleCompletionRepository;
use App\Repository\UserRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
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
        private CourseCompletionRepository $courseCompletionRepository,
        private CacheInterface $cache
    ) {
    }

    public function getUsersTotal(): int
    {
        return (int) $this->cache->get('admin_dashboard_users_total', function (ItemInterface $item) {
            $item->expiresAfter(300);
            return $this->userRepository->countVerifiedUsers();
        });
    }

    public function getCoursesTotal(): int
    {
        return (int) $this->cache->get('admin_dashboard_courses_total', function (ItemInterface $item) {
            $item->expiresAfter(300);
            return $this->courseCompletionRepository->countCoursesCompletions();
        });
    }

    public function getModulesTotal(): int
    {
        return (int) $this->cache->get('admin_dashboard_modules_total', function (ItemInterface $item) {
            $item->expiresAfter(300);
            return $this->moduleCompletionRepository->countModuleCompletions();
        });
    }

    public function getLessonsTotal(): int
    {
        return (int) $this->cache->get('admin_dashboard_lessons_total', function (ItemInterface $item) {
            $item->expiresAfter(300);
            return $this->completionRepository->countLessonsCompletions();
        });
    }
}
