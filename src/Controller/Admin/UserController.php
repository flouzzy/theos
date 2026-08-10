<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserManagementType;
use App\Repository\UserRepository;
use App\Service\SendMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin/user', name: 'admin_user_')]
class UserController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $paginator = $userRepository->findPaginatedUsers($page, $limit);
        $totalUsers = count($paginator);
        $totalPages = ceil($totalUsers / $limit);

        $users = iterator_to_array($paginator);
        $completionCounts = $userRepository->getCompletionCounts($users);

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'completionCounts' => $completionCounts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserManagementType::class, $user, [
            'action' => $this->generateUrl('admin_user_new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'New item added');

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserManagementType::class, $user, [
            'action' => $this->generateUrl('admin_user_edit', ['id' => $user->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Your data has been saved');

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/reset-password', name: 'reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SendMail $sendMail
    ): Response {
        if ($request->isMethod('POST')) {
            $submittedToken = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('reset_password_' . $user->getId(), $submittedToken)) {
                $this->addFlash('danger', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('admin_user_reset_password', ['id' => $user->getId()]);
            }

            $plainPassword = trim((string) $request->request->get('password'));
            $sendEmail = (bool) $request->request->get('send_email');

            if (empty($plainPassword) || strlen($plainPassword) < 8) {
                $this->addFlash('danger', 'Le mot de passe doit comporter au moins 8 caractères.');
                return $this->redirectToRoute('admin_user_reset_password', ['id' => $user->getId()]);
            }

            // Update user password in database
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            $entityManager->flush();

            $emailSentNotice = '';
            if ($sendEmail) {
                try {
                    $loginUrl = $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
                } catch (\Exception $e) {
                    $loginUrl = 'https://academie.lerocher.fr/login';
                }

                $sendMail->send(
                    'academie@lerocher.fr',
                    $user->getEmail(),
                    'Votre nouveau mot de passe pour l\'Académie Le Rocher',
                    'emails/admin_reset_password.html.twig',
                    [
                        'user' => $user,
                        'plainPassword' => $plainPassword,
                        'loginUrl' => $loginUrl,
                    ]
                );
                $emailSentNotice = ' et un e-mail lui a été transmis';
            }

            $this->addFlash('success', sprintf('Le mot de passe de %s a été mis à jour avec succès%s !', $user->getEmail(), $emailSentNotice));

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        // Generate a suggested random password for GET display
        $bytes = random_bytes(6);
        $generatedPassword = bin2hex($bytes);

        return $this->render('admin/user/reset_password.html.twig', [
            'user' => $user,
            'generatedPassword' => $generatedPassword,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Item deleted');
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
