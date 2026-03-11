<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\PayoutService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:payouts:generate',
    description: 'Calculates and generates payouts for creators based on previous month revenue and completions.',
)]
class GenerateMonthlyPayoutsCommand extends Command
{
    public function __construct(
        private PayoutService $payoutService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('month', null, InputOption::VALUE_OPTIONAL, 'The month to calculate (format YYYY-MM), defaults to previous month')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $monthStr = $input->getOption('month');

        if ($monthStr) {
            try {
                $month = new \DateTimeImmutable($monthStr . '-01');
            } catch (\Exception $e) {
                $io->error('Invalid month format. Please use YYYY-MM.');
                return Command::FAILURE;
            }
        } else {
            $month = new \DateTimeImmutable('last month');
        }

        $io->info(sprintf('Generating payouts for %s...', $month->format('F Y')));

        $payouts = $this->payoutService->calculateMonthlyPayouts($month);

        if (empty($payouts)) {
            $io->warning('No payouts generated. Either no revenue or no completions found.');
        } else {
            $io->success(sprintf('%d payouts generated successfully.', count($payouts)));
            
            $tableData = [];
            foreach ($payouts as $payout) {
                $tableData[] = [
                    $payout->getCreator()->getFullname(),
                    $payout->getCourse()->getTitle(),
                    $payout->getAmount() / 100 . ' ' . strtoupper($payout->getCurrency())
                ];
            }
            $io->table(['Creator', 'Course', 'Amount'], $tableData);
        }

        return Command::SUCCESS;
    }
}
