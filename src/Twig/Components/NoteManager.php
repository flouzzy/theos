<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Lesson;
use App\Entity\Note;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('NoteManager')]
final class NoteManager
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?int $lessonId = null;

    #[LiveProp(writable: true)]
    public string $newNoteContent = '';

    public function __construct(
        private readonly NoteRepository $noteRepository,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return Note[]
     */
    public function getNotes(): array
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        if ($this->lessonId) {
            $lesson = $this->entityManager->getReference(Lesson::class, $this->lessonId);
            return $this->noteRepository->findUserNotesByLesson($lesson, $user);
        }

        return $this->noteRepository->findByUser($user);
    }

    #[LiveAction]
    public function addNote(): void
    {
        if (empty(trim($this->newNoteContent))) {
            return;
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if (!$user || !$this->lessonId) {
            return;
        }

        $lesson = $this->entityManager->getReference(Lesson::class, $this->lessonId);

        $note = new Note();
        $note->setContent($this->newNoteContent);
        $note->setUser($user);
        $note->setLesson($lesson);

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        $this->newNoteContent = '';
    }

    #[LiveAction]
    public function deleteNote(#[LiveArg] int $id): void
    {
        $note = $this->noteRepository->find($id);

        if (!$note) {
            return;
        }

        if ($note->getUser() !== $this->security->getUser()) {
            return;
        }

        $this->entityManager->remove($note);
        $this->entityManager->flush();
    }
}
