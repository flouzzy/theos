<?php

namespace App\Command;

use App\Service\TriggerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:triggers:process',
    description: 'Process all daily triggers (streak warnings, AI digests, etc.)',
)]
class ProcessTriggersCommand extends Command
{
    public function __construct(
        private TriggerService $triggerService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Starting trigger processing...');

        $this->triggerService->processDailyTriggers();

        $io->success('Trigger processing completed.');

        return Command::SUCCESS;
    }
}
