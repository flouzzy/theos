<?php

namespace App\Controller;

use App\Form\UserType;
use App\Service\MediaManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/profile', name: 'profile_')]
class ProfileController extends AbstractController
{
    public function __construct(private MediaManager $mediaManager)
    {
    }

    #[Route('', name: 'index')]
    public function index(EntityManagerInterface $entityManager, TranslatorInterface $translator, Request $request): Response
    {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Sauvegarde l'image associée à la leçon
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $imageFileName = $this->mediaManager->upload($imageFile, 'user', ['maxWidth' => 350, 'maxHeight' => 350]);
                $user->setImage($imageFileName);
            }

            // !! TODO : remplacer par des events !!
            $user->setFullname($user->getFirstname() . ' ' . $user->getLastname());

            $entityManager->flush();
            $this->addFlash('success', $translator->trans('Your account has been updated'));

            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/show.html.twig', [
            'profileForm' => $form,
        ]);
    }
}
