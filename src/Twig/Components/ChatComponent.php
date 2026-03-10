<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\ChatMessage;
use App\Entity\Cohort;
use App\Entity\User;
use App\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ChatComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Cohort $cohort = null;

    #[LiveProp(writable: true)]
    public string $content = '';

    public function __construct(
        private Security $security,
        private ChatMessageRepository $chatMessageRepository,
        private EntityManagerInterface $entityManager,
        private HubInterface $hub
    ) {
    }

    /**
     * @return ChatMessage[]
     */
    public function getMessages(): array
    {
        if (!$this->cohort) {
            return [];
        }

        return $this->chatMessageRepository->findBy(
            ['cohort' => $this->cohort],
            ['createdAt' => 'ASC'],
            50
        );
    }

    #[LiveAction]
    public function sendMessage(): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user || !$this->cohort || !$this->content) {
            return;
        }

        $message = new ChatMessage();
        $message->setAuthor($user);
        $message->setCohort($this->cohort);
        $message->setContent($this->content);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Notify Mercure
        $update = new Update(
            sprintf('cohort-chat-%d', $this->cohort->getId()),
            json_encode(['id' => $message->getId(), 'author' => $user->getFullname()])
        );
        $this->hub->publish($update);

        $this->content = '';
    }
}
