<?php
$content = file_get_contents('src/Controller/CoachController.php');
$newContent = str_replace(
    <<<'SEARCH'
    public function index(): Response
    {
        // Gamification mock data
        $streak = 14; // Mock value
        $xp = 1250; // Mock value
        $level = 5; // Mock value
        $nextLevelXp = 2000;

        return $this->render('coach/index.html.twig', [
            'streak' => $streak,
            'xp' => $xp,
            'level' => $level,
            'nextLevelXp' => $nextLevelXp,
        ]);
    }
SEARCH,
    <<<'REPLACE'
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('coach/index.html.twig', [
            'streak' => $user->getStreak(),
            'xp' => $user->getXp(),
            'badgesCount' => count($user->getBadges()),
        ]);
    }
REPLACE,
    $content
);
file_put_contents('src/Controller/CoachController.php', $newContent);
echo "Updated src/Controller/CoachController.php\n";
