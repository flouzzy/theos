<?php

namespace App\Command;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:benchmark-notification',
    description: 'Benchmarks notification mark-all-as-read performance',
)]
class BenchmarkNotificationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private NotificationRepository $notificationRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_OPTIONAL, 'Number of notifications to create', 1000)
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Mode: loop or batch', 'loop')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = (int) $input->getOption('count');
        $mode = $input->getOption('mode');

        $email = 'benchmark_user_' . uniqid() . '@example.com';
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password');
        $user->setFirstname('Bench');
        $user->setLastname('Mark');

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $userId = $user->getId();
        $this->entityManager->clear();

        $io->text("Creating $count notifications for user $email...");

        $batchSize = 500;
        for ($i = 0; $i < $count; $i++) {
            $userRef = $this->entityManager->getReference(User::class, $userId);
            $notification = new Notification();
            $notification->setMessage("Notification $i");
            $notification->setUser($userRef);
            $notification->setIsRead(false);
            $this->entityManager->persist($notification);

            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->text("Notifications created. Starting benchmark ($mode)...");

        // Re-fetch user to ensure clean state
        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error('User not found.');
            return Command::FAILURE;
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        if ($mode === 'loop') {
            // Simulate the controller logic
            $notifications = $user->getNotifications();
            foreach ($notifications as $notification) {
                $notification->setIsRead(true);
            }
            $this->entityManager->flush();
        } elseif ($mode === 'batch') {
            $this->notificationRepository->markAllAsRead($user);
        } else {
            $io->error("Invalid mode: $mode");
            $this->cleanup($user);
            return Command::FAILURE;
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $duration = $endTime - $startTime;
        $memory = $endMemory - $startMemory;

        $io->success(sprintf("Time: %.4f seconds", $duration));
        $io->text(sprintf("Memory diff: %.2f MB", $memory / 1024 / 1024));

        // Cleanup
        $this->cleanup($user);

        return Command::SUCCESS;
    }

    private function cleanup(User $user): void
    {
        $userId = $user->getId();
        // Clear EM to ensure no stale objects
        $this->entityManager->clear();

        // Delete notifications
        $this->notificationRepository->createQueryBuilder('n')
            ->delete()
            ->where('n.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();

        // Delete user
        $userToDelete = $this->entityManager->find(User::class, $userId);
        if ($userToDelete) {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();
        }
    }
}
