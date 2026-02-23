<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsCommand(
    name: 'app:benchmark-users',
    description: 'Benchmarks the user list query performance',
)]
class BenchmarkUsersCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // warm up
        $this->userRepository->findPaginatedUsers(1, 20);
        $this->entityManager->clear();

        $stopwatch = new Stopwatch();
        $stopwatch->start('user-list');

        $queryCount = 0;
        // Hook into Doctrine to count queries
        $logger = new \Doctrine\DBAL\Logging\DebugStack();
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger($logger);

        $paginator = $this->userRepository->findPaginatedUsers(1, 20);
        $users = iterator_to_array($paginator);

        foreach ($users as $user) {
            // Simulate template behavior
            $completedCourses = 0;
            foreach ($user->getCourseCompletions() as $completion) {
                if ($completion->isCompleted()) {
                    $completedCourses++;
                }
            }

            $completedModules = 0;
            foreach ($user->getModuleCompletions() as $completion) {
                if ($completion->isCompleted()) {
                    $completedModules++;
                }
            }
        }

        $event = $stopwatch->stop('user-list');
        $queryCount = count($logger->queries);

        $output->writeln(sprintf('Time: %d ms', $event->getDuration()));
        $output->writeln(sprintf('Memory: %.2f MB', $event->getMemory() / 1024 / 1024));
        $output->writeln(sprintf('Queries: %d', $queryCount));
        $output->writeln(sprintf('Users fetched: %d', count($users)));

        return Command::SUCCESS;
    }
}
