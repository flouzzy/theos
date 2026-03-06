<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// Setup query logger
$logger = new \Doctrine\DBAL\Logging\DebugStack();
$em->getConnection()->getConfiguration()->setSQLLogger($logger);

$userRepository = $em->getRepository(\App\Entity\User::class);
$moduleCompletionRepository = $em->getRepository(\App\Entity\ModuleCompletion::class);
$completionRepository = $em->getRepository(\App\Entity\Completion::class);

// Get the first user
$user = $userRepository->findOneBy([]);
if (!$user) {
    echo "No user found in the database. Cannot benchmark.\n";
    exit(1);
}

// Ensure proxy generation
$em->clear();
$user = $em->merge($user);

$logger->queries = []; // reset queries

$startTime = microtime(true);

$moduleCompletions = $moduleCompletionRepository->findWithScoreByUser($user);
$lessonCompletions = $completionRepository->findWithScoreByUser($user);

$evaluations = [];
$scores = [];

// Process Module Completions
foreach ($moduleCompletions as $mc) {
    if ($mc->getScore() !== null) {
        $scores[] = $mc->getScore();
        $title = $mc->getModule() ? $mc->getModule()->getTitle() : 'Module';
        $course = $mc->getModule() && $mc->getModule()->getCourses()->first() ? $mc->getModule()->getCourses()->first()->getTitle() : 'Module';
    }
}

// Process Lesson Completions (Quizzes)
foreach ($lessonCompletions as $lc) {
    if ($lc->getScore() !== null) {
        $scores[] = $lc->getScore();
        $title = $lc->getLesson() ? $lc->getLesson()->getTitle() : 'Lesson';
        $course = $lc->getLesson() && $lc->getLesson()->getModule() ? $lc->getLesson()->getModule()->getTitle() : 'Lesson';
        $duration = $lc->getLesson() && $lc->getLesson()->getDuration() ? $lc->getLesson()->getDuration() . ' min' : '10 min';
    }
}

$endTime = microtime(true);

$queryCount = count($logger->queries);
$timeTaken = ($endTime - $startTime) * 1000;

echo "Query count: $queryCount\n";
echo "Execution time: " . round($timeTaken, 2) . " ms\n";
