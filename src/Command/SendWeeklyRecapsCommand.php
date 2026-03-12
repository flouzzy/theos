<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\WeeklyRecapService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:emails:weekly-recap',
    description: 'Sends weekly activity recap emails to all users who opted in.',
)]
class SendWeeklyRecapsCommand extends Command
{
    public function __construct(
        private WeeklyRecapService $weeklyRecapService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Sending Weekly Recaps');

        $count = $this->weeklyRecapService->sendWeeklyRecaps();

        if ($count > 0) {
            $io->success(sprintf('%d weekly recap emails sent successfully.', $count));
        } else {
            $io->warning('No weekly recap emails were sent (either no opted-in users or no activity).');
        }

        return Command::SUCCESS;
    }
}
