<?php

require __DIR__.'/vendor/autoload.php';

use App\Twig\Components\AdminDashboard;
use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Repository\ModuleCompletionRepository;
use App\Repository\UserRepository;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\ItemInterface;

$userRepository = new class extends UserRepository {
    public function __construct() {}
    public function countVerifiedUsers(): int {
        usleep(50000); // 50ms simulation of DB query
        return 100;
    }
};

$moduleCompletionRepository = new class extends ModuleCompletionRepository {
    public function __construct() {}
    public function countModuleCompletions(): int {
        usleep(50000);
        return 50;
    }
};

$completionRepository = new class extends CompletionRepository {
    public function __construct() {}
    public function countLessonsCompletions(): int {
        usleep(50000);
        return 200;
    }
};

$courseCompletionRepository = new class extends CourseCompletionRepository {
    public function __construct() {}
    public function countCoursesCompletions(): int {
        usleep(50000);
        return 20;
    }
};

$cache = new ArrayAdapter();

class AdminDashboardCached extends AdminDashboard {
    public function __construct(
        private UserRepository $userRepository,
        private ModuleCompletionRepository $moduleCompletionRepository,
        private CompletionRepository $completionRepository,
        private CourseCompletionRepository $courseCompletionRepository,
        private \Symfony\Contracts\Cache\CacheInterface $cache
    ) {}

    public function getUsersTotal(): int {
        return $this->cache->get('admin_dashboard_users_total', function (ItemInterface $item): int {
            $item->expiresAfter(300);
            return $this->userRepository->countVerifiedUsers();
        });
    }

    public function getCoursesTotal(): int {
        return $this->cache->get('admin_dashboard_courses_total', function (ItemInterface $item): int {
            $item->expiresAfter(300);
            return $this->courseCompletionRepository->countCoursesCompletions();
        });
    }

    public function getModulesTotal(): int {
        return $this->cache->get('admin_dashboard_modules_total', function (ItemInterface $item): int {
            $item->expiresAfter(300);
            return $this->moduleCompletionRepository->countModuleCompletions();
        });
    }

    public function getLessonsTotal(): int {
        return $this->cache->get('admin_dashboard_lessons_total', function (ItemInterface $item): int {
            $item->expiresAfter(300);
            return $this->completionRepository->countLessonsCompletions();
        });
    }
}

$dashboard = new AdminDashboardCached(
    $userRepository,
    $moduleCompletionRepository,
    $completionRepository,
    $courseCompletionRepository,
    $cache
);

$start = microtime(true);

for ($i = 0; $i < 100; $i++) {
    $dashboard->getUsersTotal();
    $dashboard->getCoursesTotal();
    $dashboard->getModulesTotal();
    $dashboard->getLessonsTotal();
}

$end = microtime(true);

echo "Cached time: " . ($end - $start) . " seconds\n";
