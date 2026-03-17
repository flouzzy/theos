<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\JWT;
use App\Service\SendMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private JWT $jwt,
        private SendMail $mailer,
        private \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher,
        #[Autowire('%default_from_email%')] private string $senderEmail,
        #[Autowire('%default_from_name%')] private string $senderName,
    ) {
    }

    #[Route('/register', name: 'register', priority: 3)]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security): Response
    {
        // Check if user is already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('register'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // generate a signed url and email it to the user
            $this->sendEmailConfirmation($user);

            // Force user login
            $security->login($user, 'form_login');

            if ($request->getSession()->has('pending_cohort_token')) {
                return $this->redirectToRoute('cohort_complete_join');
            }

            return $this->redirectToRoute('home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email/{token}', name: 'verify_email', priority: 3)]
    public function verifyUserEmail(string $token, UserRepository $userRepository): Response
    {

        //On vérifie si le token est valide, n'a pas expiré et n'a pas été modifié
        /** @var string $jwtSecret */
        $jwtSecret = $this->getParameter('app.jwtsecret');
        if ($this->jwt->isValid($token) && !$this->jwt->isExpired($token) && $this->jwt->check($token, $jwtSecret)) {
            // On récupère le payload
            $payload = $this->jwt->getPayload($token);

            // On récupère le user du token
            /**
             * @var \App\Entity\User|null $user
             */
            $user = $userRepository->find($payload['user_id']);

            //On vérifie que l'utilisateur existe et n'a pas encore activé son compte
            if ($user && !$user->isVerified()) {
                $user->setIsVerified(true);
                $this->entityManager->flush();

                // Dispatch event
                $this->eventDispatcher->dispatch(new \App\Event\UserVerifiedEvent($user));

                $this->addFlash('success', 'Your email address has been verified');
                return $this->redirectToRoute('home');
            }

            if ($user && $user->isVerified()) {
                $this->addFlash('success', 'Your email address has been verified');
                return $this->redirectToRoute('home');
            }
        }
        // Ici un problème se pose dans le token
        $this->addFlash('danger', 'The token is invalid or has expired');

        return $this->redirectToRoute('home');
    }

    #[Route('/verify/resend-email', name: 'verify_email_resend', priority: 3)]
    public function resendVerifyEmail(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();

        // Send verification email
        $this->sendEmailConfirmation($user);

        $this->addFlash('success', 'A confirmation e-mail has been sent to you');

        return $this->redirectToRoute('home');
    }

    /**
     * Send verification email
     *
     * @param User $user
     * @return void
     */
    private function sendEmailConfirmation(User $user)
    {
        // generate a signed url and email it to the user

        // On génère le JWT de l'utilisateur
        // On crée le Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        // On crée le Payload
        $payload = [
            'user_id' => $user->getId()
        ];

        // On génère le token
        /** @var string $jwtSecret */
        $jwtSecret = $this->getParameter('app.jwtsecret');
        $token = $this->jwt->generate($header, $payload, $jwtSecret);

        $signedUrl = $this->generateUrl('verify_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        // On envoie un mail        
        $this->mailer->send(
            new Address($this->senderEmail, $this->senderName),
            (string) $user->getEmail(),
            $this->translator->trans('Please confirm your email'),
            'registration/confirmation_email.html.twig',
            [
                'user' => [
                    'firstname' => $user->getFirstname(),
                    'email' => $user->getEmail()
                ],
                'signedUrl' => $signedUrl
            ]
        );
    }
}
