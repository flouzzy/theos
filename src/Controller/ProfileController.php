<?php

namespace App\Controller;


use App\Form\UserType;
use App\Service\BrevoApi;
use App\Service\MediaManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/profile', name: 'profile_')]
#[IsGranted('IS_AUTHENTICATED')]
class ProfileController extends AbstractController
{
    public function __construct(private MediaManager $mediaManager)
    {
    }

    #[Route('', name: 'index', priority: 3)]
    public function index(EntityManagerInterface $entityManager, TranslatorInterface $translator, Request $request, BrevoApi $brevoApi): Response
    {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('profile_index'),
        ]);

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

            // Maj brevo
            $brevoApi->addOrUpdateContact($user);

            $entityManager->flush();
            $this->addFlash('success', $translator->trans('Your account has been updated'));

            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/show.html.twig', [
            'profileForm' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): Response
    {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Forcer la déconnexion de l'utilisateur
            $tokenStorage->setToken(null);

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your account has been successfully deleted');
        }

        return $this->redirectToRoute('login', [], Response::HTTP_SEE_OTHER);
    }
}
