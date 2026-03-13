<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Event\LessonCompleteEvent;
use App\Repository\CompletionRepository;
use App\Service\CompletionService;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

#[AsLiveComponent('LessonItem')]
final class LessonItem
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public Lesson $lesson;

    #[LiveProp]
    public Module $module;

    #[LiveProp]
    public Course $course;

    #[LiveProp(writable: true)]
    public bool $isCompleted = false;

    #[LiveProp(writable: true)]
    public bool $needsReview = false;

    #[LiveProp]
    public bool $isLocked = false;

    public function __construct(
        private readonly CompletionRepository $completionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly CompletionService $completionService,
        private readonly GamificationService $gamificationService,
    ) {
    }

    #[LiveAction]
    public function toggleCompletion(): void
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if (!$user || $this->isLocked) {
            return;
        }

        $newStatus = !$this->isCompleted;

        // Find or create completion record
        $completion = $this->completionRepository->findOneBy([
            'user' => $user,
            'lesson' => $this->lesson,
        ]);

        $wasCompleted = $completion && $completion->isCompleted();

        if (!$completion) {
            $completion = new \App\Entity\Completion();
        }

        $completion->setUser($user);
        $completion->setLesson($this->lesson);
        $completion->setCompleted($newStatus);

        $this->completionService->setModuleCompletion($this->module);
        $this->completionService->setCourseCompletion($this->course);

        $this->entityManager->persist($completion);
        $this->entityManager->flush();

        // Dispatch event
        $event = new LessonCompleteEvent($this->lesson, $user, $newStatus, $wasCompleted);
        $this->dispatcher->dispatch($event);

        if ($newStatus && !$wasCompleted) {
            $this->dispatchBrowserEvent('confetti:fire');
        }

        $this->isCompleted = $newStatus;
    }

    #[LiveAction]
    public function toggleNeedsReview(): void
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if (!$user || $this->isLocked) {
            return;
        }

        $newStatus = !$this->needsReview;

        $completion = $this->completionRepository->findOneBy([
            'user' => $user,
            'lesson' => $this->lesson,
        ]);

        if (!$completion) {
            $completion = new \App\Entity\Completion();
            $completion->setUser($user);
            $completion->setLesson($this->lesson);
        }

        $completion->setNeedsReview($newStatus);
        $this->entityManager->persist($completion);
        $this->entityManager->flush();

        $this->needsReview = $newStatus;
    }
}
