<?php

namespace App\Twig\Components;

use App\Entity\Skill;
use App\Entity\User;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class UserSkillsComponent
{
    use DefaultActionTrait;

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
    public function getSkills(): iterable
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $user ? $user->getSkills() : [];
    }

    #[LiveAction]
    public function addSkill(): void
    {
        $name = trim($this->newSkillName);
        if (!$name) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        $skill = $this->skillRepository->findOneBy(['name' => $name]);
        if (!$skill) {
            $skill = new Skill();
            $skill->setName($name);
            $this->entityManager->persist($skill);
        }

        if (!$user->getSkills()->contains($skill)) {
            $user->addSkill($skill);
            $this->entityManager->flush();
        }

        $this->newSkillName = '';
    }

    #[LiveAction]
    public function removeSkill(#[LiveArg] int $skillId): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $skill = $this->skillRepository->find($skillId);

        if ($user && $skill && $user->getSkills()->contains($skill)) {
            $user->removeSkill($skill);
            $this->entityManager->flush();
        }
    }
}
