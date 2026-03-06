<?php

require __DIR__.'/vendor/autoload.php';

use App\Twig\Components\AdminDashboard;
use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Repository\ModuleCompletionRepository;
use App\Repository\UserRepository;

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

$dashboard = new AdminDashboard(
    $userRepository,
    $moduleCompletionRepository,
    $completionRepository,
    $courseCompletionRepository
);

$start = microtime(true);

for ($i = 0; $i < 100; $i++) {
    $dashboard->getUsersTotal();
    $dashboard->getCoursesTotal();
    $dashboard->getModulesTotal();
    $dashboard->getLessonsTotal();
}

$end = microtime(true);

echo "Baseline time: " . ($end - $start) . " seconds\n";
