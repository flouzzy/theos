<?php

namespace App;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Console\Messenger\RunCommandMessage;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->add(
                RecurringMessage::cron('0 2 * * *', new RunCommandMessage('app:recalculate-leaderboard')),
                RecurringMessage::cron('0 * * * *', new RunCommandMessage('reset-password:remove-expired'))
            )
        ;
    }
}
