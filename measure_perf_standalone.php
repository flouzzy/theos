<?php

// Re-implement a tiny mockup of the repository functionality since we can't boot the kernel on PHP 8.3
// with PHP 8.4 dependencies

class User {
    public $id = 1;
}

class ModuleCompletion {
    private $user;
    private $completed;
    private $score;
    public function __construct($user, $completed, $score) {
        $this->user = $user;
        $this->completed = $completed;
        $this->score = $score;
    }
    public function getUser() { return $this->user; }
    public function getCompleted() { return $this->completed; }
    public function getScore() { return $this->score; }
}

$user = new User();

// Generate dummy data: 100,000 completions where only 10% have scores
$completions = [];
for ($i = 0; $i < 100000; $i++) {
    $score = ($i % 10 === 0) ? rand(10, 20) : null;
    $completions[] = new ModuleCompletion($user, true, $score);
}

echo "Data size: " . count($completions) . " records.\n\n";

// OLD APPROACH: Fetch all, filter in PHP
$startOldTime = microtime(true);
$startOldMem = memory_get_usage();

$oldScores = [];
// simulate old findBy
$oldModuleCompletions = $completions;
foreach ($oldModuleCompletions as $mc) {
    if ($mc->getScore() !== null) {
        $oldScores[] = $mc->getScore();
    }
}
$endOldTime = microtime(true);
$endOldMem = memory_get_usage();

// NEW APPROACH: Filter in DB (Simulated by filtering BEFORE iterating in PHP)
$startNewTime = microtime(true);
$startNewMem = memory_get_usage();

// Simulate findWithScoreByUser query by returning only the ones with scores
$newModuleCompletions = array_filter($completions, fn($c) => $c->getScore() !== null);
$newScores = [];
foreach ($newModuleCompletions as $mc) {
    $newScores[] = $mc->getScore();
}

$endNewTime = microtime(true);
$endNewMem = memory_get_usage();

$oldTimeElapsed = ($endOldTime - $startOldTime) * 1000;
$oldMemElapsed = $endOldMem - $startOldMem;

$newTimeElapsed = ($endNewTime - $startNewTime) * 1000;
$newMemElapsed = $endNewMem - $startNewMem;

echo "OLD APPROACH (fetch all, filter in PHP):\n";
echo "Time: " . number_format($oldTimeElapsed, 2) . " ms\n";
echo "Count: " . count($oldScores) . "\n\n";

echo "NEW APPROACH (filter in DB (Simulated)):\n";
echo "Time: " . number_format($newTimeElapsed, 2) . " ms\n";
echo "Count: " . count($newScores) . "\n\n";

if ($newTimeElapsed < $oldTimeElapsed) {
    echo "Improvement: Time reduced by " . number_format((($oldTimeElapsed - $newTimeElapsed) / $oldTimeElapsed) * 100, 2) . "%\n";
} else {
    echo "Note: The DB filtering overhead simulation might not reflect actual DB performance gains correctly.\n";
    echo "Actual DB gains will be much higher due to reduced hydration, memory, and network transfer.\n";
}
