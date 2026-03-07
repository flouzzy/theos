<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CoachExtension extends AbstractExtension
{
    public function __construct(
        #[Autowire(env: 'bool:COACH_ENABLED')]
        private bool $coachEnabled
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('coach_enabled', [$this, 'isCoachEnabled']),
        ];
    }

    public function isCoachEnabled(): bool
    {
        return $this->coachEnabled;
    }
}
