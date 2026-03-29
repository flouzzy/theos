<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\CompletionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recalculate-leaderboard',
    description: 'Recalculate XP for all users based on their completions',
)]
class RecalculateLeaderboardCommand extends Command
{
    private const XP_PER_COMPLETION = 10;

    public function __construct(
        private UserRepository $userRepository,
        private CompletionRepository $completionRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Recalculating leaderboard XP');

        $users = $this->userRepository->findAll();
        $updatedCount = 0;

        // Fetch all completion counts grouped by user to avoid N+1 query
        $completionCounts = $this->completionRepository->countAllCompletionsGroupedByUser();

        foreach ($users as $user) {
            $completionCount = $completionCounts[$user->getId()] ?? 0;
            $expectedXp = $completionCount * self::XP_PER_COMPLETION;

            $currentXp = $user->getXp();

            if ($currentXp !== $expectedXp) {
                $user->setXp($expectedXp);
                $this->entityManager->persist($user);
                $updatedCount++;

                $io->text(sprintf(
                    '  %s: %d XP → %d XP (%d completions)',
                    $user->getFullname(),
                    $currentXp,
                    $expectedXp,
                    $completionCount
                ));
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Done! Updated %d / %d users.', $updatedCount, count($users)));

        return Command::SUCCESS;
    }
}
