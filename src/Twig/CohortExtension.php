<?php

namespace App\Twig;

use App\Service\CohortSession;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CohortExtension extends AbstractExtension
{
    public function __construct(
        private CohortSession $cohortSession
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('active_cohort', [$this->cohortSession, 'getSelectedCohort']),
        ];
    }
}
