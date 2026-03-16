<?php

namespace App\Controller;

use App\Entity\Cohort;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cohort', name: 'cohort_')]
#[IsGranted('IS_AUTHENTICATED')]
class ChatController extends AbstractController
{
    #[Route('/{id}/chat', name: 'chat')]
    public function show(Cohort $cohort, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        // Check if user is in cohort
        if (!$cohort->getUsers()->contains($user)) {
            throw $this->createAccessDeniedException();
        }

        $conversation = $cohort->getConversation();
        if (!$conversation) {
            $conversation = new Conversation();
            $cohort->setConversation($conversation);
            $entityManager->persist($conversation);
            $entityManager->flush();
        }

        // Handle new message
        if ($request->isMethod('POST')) {
             $content = $request->request->get('content');
             $token = $request->request->get('_token');

             if (is_string($token) && $this->isCsrfTokenValid('chat', $token) && is_string($content)) {
                 if (!$user instanceof User) {
                     throw $this->createAccessDeniedException();
                 }

                 $message = new Message();
                 $message->setContent($content);
                 $message->setSender($user);
                 $message->setConversation($conversation);
                 $entityManager->persist($message);
                 $entityManager->flush();

                 return $this->redirectToRoute('cohort_chat', ['id' => $cohort->getId()]);
             }
        }

        return $this->render('chat/show.html.twig', [
            'cohort' => $cohort,
            'conversation' => $conversation
        ]);
    }

    #[Route('/private/{id}', name: 'private')]
    public function private(User $targetUser, EntityManagerInterface $em, ConversationRepository $repo): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user === $targetUser) return $this->redirectToRoute('cohort_index');

        $conversation = $repo->findPrivateConversation($user, $targetUser) ?: new Conversation();
        if (!$conversation->getId()) {
            $conversation->setIsPrivate(true);
            $conversation->addParticipant($user);
            $conversation->addParticipant($targetUser);
            $em->persist($conversation);
            $em->flush();
        }

        return $this->render('chat/show.html.twig', [
            'conversation' => $conversation,
            'targetUser' => $targetUser
        ]);
    }
}
