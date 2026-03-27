<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SkillEndorsement;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/endorse', name: 'endorse_')]
#[IsGranted('IS_AUTHENTICATED')]
class SkillEndorsementController extends AbstractController
{
    #[Route('/{userId}/{skillId}', name: 'add', methods: ['POST'])]
    public function add(int $userId, int $skillId, EntityManagerInterface $em, UserRepository $userRepo, SkillRepository $skillRepo): Response
    {
        $receiver = $userRepo->find($userId);
        $skill = $skillRepo->find($skillId);
        
        if (!$receiver || !$skill) {
            throw $this->createNotFoundException();
        }

        $endorsement = new SkillEndorsement();
        $endorsement->setReceiver($receiver);
        $endorsement->setGiver($this->getUser());
        $endorsement->setSkill($skill);
        
        $em->persist($endorsement);
        $em->flush();
        
        $this->addFlash('success', 'Compétence approuvée !');
        
        return $this->redirectToRoute('profile_public', ['id' => $userId]);
    }
}
