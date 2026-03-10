<?php

namespace App\Twig\Components;

use App\Entity\Skill;
use App\Entity\User;
use App\Repository\SkillRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Doctrine\Common\Collections\ArrayCollection;

#[AsLiveComponent]
class UserSkillsComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?User $user = null;

    #[LiveProp(writable: true)]
    public string $newSkillName = '';

    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private SkillRepository $skillRepository
    ) {
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection|iterable
    {
        return $this->user ? $this->user->getSkills() : new ArrayCollection();
    }

    #[LiveAction]
    public function addSkill(): void
    {
        $this->denyAccessUnlessOwner();

        $name = trim($this->newSkillName);
        if (!$name) {
            return;
        }

        if (!$this->user) {
            return;
        }

        $skill = $this->skillRepository->findOneBy(['name' => $name]);
        if (!$skill) {
            $skill = new Skill();
            $skill->setName($name);
            $this->entityManager->persist($skill);
        }

        if (!$this->user->getSkills()->contains($skill)) {
            $this->user->addSkill($skill);
            $this->entityManager->flush();
        }

        $this->newSkillName = '';
    }

    #[LiveAction]
    public function removeSkill(#[LiveArg] int $skillId): void
    {
        $this->denyAccessUnlessOwner();

        $skill = $this->skillRepository->find($skillId);

        if ($this->user && $skill && $this->user->getSkills()->contains($skill)) {
            $this->user->removeSkill($skill);
            $this->entityManager->flush();
        }
    }

    private function denyAccessUnlessOwner(): void
    {
        /** @var User|null $currentUser */
        $currentUser = $this->security->getUser();

        if (!$currentUser || !$this->user || $currentUser->getId() !== $this->user->getId()) {
            throw new AccessDeniedException('You are not the owner of this profile.');
        }
    }
}
