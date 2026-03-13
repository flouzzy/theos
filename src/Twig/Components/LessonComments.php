<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Comment;
use App\Entity\Lesson;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('LessonComments')]
final class LessonComments
{
    use DefaultActionTrait;

    #[LiveProp]
    public Lesson $lesson;

    #[LiveProp(writable: true)]
    public string $content = '';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly GamificationService $gamificationService
    ) {
    }

    #[LiveAction]
    public function addComment(): void
    {
        if (empty(trim($this->content))) {
            return;
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        $comment = new Comment();
        $comment->setContent($this->content);
        $comment->setUser($user);
        $comment->setLesson($this->lesson);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->gamificationService->addXp($user, 5, 'comment_posted');

        $this->content = '';
        
        // Refresh the lesson relation to include the new comment
        $this->entityManager->refresh($this->lesson);
    }
}
