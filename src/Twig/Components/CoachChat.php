<?php

namespace App\Twig\Components;

use App\Service\CoachAIAgent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('CoachChat')]
class CoachChat
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $newMessage = '';

    /**
     * @var array<array{role: string, content: string}>
     */
    #[LiveProp]
    public array $history = [];

    public function __construct(
        private CoachAIAgent $aiAgent,
        private Security $security
    ) {
    }

    public function mount(): void
    {
        $user = $this->security->getUser();
        if ($user) {
            /** @var \App\Entity\User $user */
            $this->history = $this->aiAgent->getHistory($user);
        }
    }

    #[LiveAction]
    public function sendMessage(): void
    {
        if (empty(trim($this->newMessage))) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        /** @var \App\Entity\User $user */
        $reply = $this->aiAgent->generateResponse($user, $this->newMessage);
        
        // Refresh history
        $this->history = $this->aiAgent->getHistory($user);
        $this->newMessage = '';
    }
}
