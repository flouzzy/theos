<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CompletionRepository;
use App\Repository\ModuleCompletionRepository;

// Boot kernel
$kernel = new Kernel('test', true);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();
/** @var CompletionRepository $completionRepository */
$completionRepository = $em->getRepository(\App\Entity\Completion::class);
/** @var ModuleCompletionRepository $moduleCompletionRepository */
$moduleCompletionRepository = $em->getRepository(\App\Entity\ModuleCompletion::class);

// Find a user or create a dummy user
$user = $em->getRepository(User::class)->findOneBy([]);

if (!$user) {
    echo "No user found in DB. Benchmark results might not be significant.\n";
    // For a real benchmark we'd insert a lot of completions here, but since it's just to check logic
    // we'll run it as is.
    exit(0);
}

// Warmup DB connection
$completionRepository->findBy(['user' => $user, 'completed' => true]);

// Benchmark Old Way
$startOldTime = microtime(true);
$startOldMemory = memory_get_usage();

$oldModuleCompletions = $moduleCompletionRepository->findBy(['user' => $user, 'completed' => true]);
$oldLessonCompletions = $completionRepository->findBy(['user' => $user, 'completed' => true]);

$oldScores = [];
foreach ($oldModuleCompletions as $mc) {
    if ($mc->getScore() !== null) {
        $oldScores[] = $mc->getScore();
    }
}
foreach ($oldLessonCompletions as $lc) {
    if ($lc->getScore() !== null) {
        $oldScores[] = $lc->getScore();
    }
}

$endOldTime = microtime(true);
$endOldMemory = memory_get_usage();

// Reset EM to clear memory
$em->clear();

// Benchmark New Way
$startNewTime = microtime(true);
$startNewMemory = memory_get_usage();

$newModuleCompletions = $moduleCompletionRepository->findWithScoreByUser($user);
$newLessonCompletions = $completionRepository->findWithScoreByUser($user);

$newScores = [];
foreach ($newModuleCompletions as $mc) {
    $newScores[] = $mc->getScore();
}
foreach ($newLessonCompletions as $lc) {
    $newScores[] = $lc->getScore();
}

$endNewTime = microtime(true);
$endNewMemory = memory_get_usage();

// Results
$oldTimeElapsed = ($endOldTime - $startOldTime) * 1000;
$oldMemoryElapsed = $endOldMemory - $startOldMemory;

$newTimeElapsed = ($endNewTime - $startNewTime) * 1000;
$newMemoryElapsed = $endNewMemory - $startNewMemory;

echo "Old approach:\n";
echo "Time: " . number_format($oldTimeElapsed, 4) . " ms\n";
echo "Memory allocated during run: " . number_format($oldMemoryElapsed / 1024, 2) . " KB\n";
echo "Count: " . count($oldScores) . "\n\n";

echo "New approach:\n";
echo "Time: " . number_format($newTimeElapsed, 4) . " ms\n";
echo "Memory allocated during run: " . number_format($newMemoryElapsed / 1024, 2) . " KB\n";
echo "Count: " . count($newScores) . "\n\n";

if ($newTimeElapsed < $oldTimeElapsed) {
    echo "Improvement: Time reduced by " . number_format((($oldTimeElapsed - $newTimeElapsed) / $oldTimeElapsed) * 100, 2) . "%\n";
} else {
    echo "Note: On very small datasets, overhead might make the new approach slightly slower in wall-clock time.\n";
}
if ($newMemoryElapsed < $oldMemoryElapsed) {
    echo "Improvement: Memory reduced by " . number_format((($oldMemoryElapsed - $newMemoryElapsed) / $oldMemoryElapsed) * 100, 2) . "%\n";
}
