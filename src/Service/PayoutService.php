<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Course;
use App\Entity\Payout;
use App\Repository\CompletionRepository;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class PayoutService
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private CompletionRepository $completionRepository,
        private CourseRepository $courseRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function calculateMonthlyPayouts(\DateTimeImmutable $month): array
    {
        $start = $month->modify('first day of this month')->setTime(0, 0, 0);
        $end = $month->modify('last day of this month')->setTime(23, 59, 59);

        // 1. Total revenue
        $totalRevenue = $this->transactionRepository->findTotalRevenueBetween($start, $end);
        if ($totalRevenue <= 0) {
            return [];
        }

        // 2. Total completions in period
        $courses = $this->courseRepository->findAll();
        $totalCompletions = 0;
        $courseCompletions = [];

        foreach ($courses as $course) {
            $count = $this->completionRepository->countCompletionsForCourseBetween($course, $start, $end);
            if ($count > 0) {
                $courseCompletions[$course->getId()] = [
                    'course' => $course,
                    'count' => $count
                ];
                $totalCompletions += $count;
            }
        }

        if ($totalCompletions <= 0) {
            return [];
        }

        // 3. Calculate and save payouts
        $payouts = [];
        foreach ($courseCompletions as $data) {
            /** @var Course $course */
            $course = $data['course'];
            $count = $data['count'];
            $creator = $course->getAuthor();

            if (!$creator || $course->getRevenueSharePercentage() <= 0) {
                continue;
            }

            // Allocation: (Revenue * CourseRatio) * CreatorShare%
            $courseRatio = $count / $totalCompletions;
            $courseRevenue = $totalRevenue * $courseRatio;
            $payoutAmount = (int) ($courseRevenue * ($course->getRevenueSharePercentage() / 100));

            if ($payoutAmount > 0) {
                $payout = new Payout();
                $payout->setCourse($course);
                $payout->setCreator($creator);
                $payout->setAmount($payoutAmount);
                $payout->setPeriodStart($start);
                $payout->setPeriodEnd($end);
                $payout->setStatus('pending');

                $this->entityManager->persist($payout);
                $payouts[] = $payout;
            }
        }

        $this->entityManager->flush();

        return $payouts;
    }
}
