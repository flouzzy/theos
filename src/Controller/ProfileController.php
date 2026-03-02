<?php

namespace App\Controller;


use App\Entity\PortfolioProject;
use App\Entity\Skill;
use App\Event\UserUpdatedEvent;
use App\Form\UserType;
use App\Repository\CompletionRepository;
use App\Service\MediaManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function index(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        CompletionRepository $completionRepository,
        \App\Repository\CourseCompletionRepository $courseCompletionRepository
    ): Response {
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

            $eventDispatcher->dispatch(new UserUpdatedEvent($user));

            $entityManager->flush();
            $this->addFlash('success', $translator->trans('Your account has been updated'));

            return $this->redirectToRoute('profile_index');
        }

        // Calculate Stats
        $coursesEnrolled = $user->getCourses();
        $completedCoursesCount = $courseCompletionRepository->countCompletedCoursesForUser($user);
        $notesCount = $user->getNotes()->count();

        $totalMinutes = $completionRepository->countTotalDurationByUser($user);
        $learningHours = floor($totalMinutes / 60);

        return $this->render('profile/show.html.twig', [
            'profileForm' => $form,
            'stats' => [
                'enrolled' => $coursesEnrolled->count(),
                'completed' => $completedCoursesCount,
                'hours' => $learningHours,
                'notes' => $notesCount,
                'xp' => $user->getXp(),
                'streak' => $user->getStreak(),
            ],
            'badges' => $user->getBadges(),
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

    #[Route('/add-skill', name: 'add_skill', methods: ['POST'])]
    public function addSkill(Request $request, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_skill', $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('profile_index');
        }

        $skillName = trim($request->request->get('name'));
        if ($skillName) {
            $user = $this->getUser();
            $skillRepo = $entityManager->getRepository(Skill::class);
            $skill = $skillRepo->findOneBy(['name' => $skillName]);

            if (!$skill) {
                $skill = new Skill();
                $skill->setName($skillName);
                $entityManager->persist($skill);
            }

            if (!$user->getSkills()->contains($skill)) {
                $user->addSkill($skill);
                $entityManager->flush();
                $this->addFlash('success', 'Skill added!');
            }
        }

        return $this->redirectToRoute('profile_index');
    }

    #[Route('/add-portfolio', name: 'add_portfolio', methods: ['POST'])]
    public function addPortfolio(Request $request, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_portfolio', $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('profile_index');
        }

        $title = trim($request->request->get('title'));
        $description = trim($request->request->get('description'));
        $url = trim($request->request->get('url'));

        if ($url && (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https']))) {
            $this->addFlash('error', 'Invalid URL. Only http and https are allowed.');
            return $this->redirectToRoute('profile_index');
        }

        if ($title) {
            $user = $this->getUser();
            $project = new PortfolioProject();
            $project->setTitle($title);
            $project->setDescription($description);
            $project->setUrl($url);
            $project->setUser($user);

            $entityManager->persist($project);
            $entityManager->flush();
            $this->addFlash('success', 'Project added to portfolio!');
        }

        return $this->redirectToRoute('profile_index');
    }
}
