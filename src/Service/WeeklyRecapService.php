<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\UserRepository;
use App\Repository\XpTransactionRepository;
use Symfony\Component\Mime\Address;

class WeeklyRecapService
{
    public function __construct(
        private UserRepository $userRepository,
        private XpTransactionRepository $xpTransactionRepository,
        private CompletionRepository $completionRepository,
        private SendMail $sendMail,
        private string $defaultFromEmail,
        private string $defaultFromName
    ) {}

    public function sendWeeklyRecaps(): int
    {
        $users = $this->userRepository->findBy(['weeklySummary' => true]);
        $count = 0;

        $end = new \DateTimeImmutable('now');
        $start = $end->modify('-7 days')->setTime(0, 0, 0);

        $xpGains = $this->xpTransactionRepository->findXpGainedByUsersBetween($users, $start, $end);
        $completionCounts = $this->completionRepository->countByUsersBetween($users, $start, $end);

        foreach ($users as $user) {
            $userId = (int) $user->getId();
            $xpGained = $xpGains[$userId] ?? 0;
            $completions = $completionCounts[$userId] ?? 0;

            if ($this->sendRecapForUser($user, $start, $end, $xpGained, $completions)) {
                $count++;
            }
        }

        return $count;
    }

    public function sendRecapForUser(
        User $user,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        ?int $xpGained = null,
        ?int $completions = null
    ): bool {
        $xpGained ??= $this->xpTransactionRepository->findXpGainedByUserBetween($user, $start, $end);

        // Skip if no activity
        if ($xpGained <= 0) {
            return false;
        }

        $completions ??= $this->completionRepository->countByUserBetween($user, $start, $end);

        $this->sendMail->send(
            new Address($this->defaultFromEmail, $this->defaultFromName),
            (string) $user->getEmail(),
            "📊 Ton récapitulatif hebdomadaire - Le Rocher Académie",
            'emails/weekly_recap.html.twig',
            [
                'user' => $user,
                'xpGained' => $xpGained,
                'completions' => $completions,
                'periodStart' => $start,
                'periodEnd' => $end,
            ]
        );

        return true;
    }
}
