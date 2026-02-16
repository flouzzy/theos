<?php

namespace App\Controller;

use App\Entity\Cohort;
use App\Entity\Conversation;
use App\Entity\Message;
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
        // Check if user is in cohort
        if (!$cohort->getUsers()->contains($this->getUser())) {
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

             if ($this->isCsrfTokenValid('chat', $token) && $content) {
                 $message = new Message();
                 $message->setContent($content);
                 $message->setSender($this->getUser());
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
}
