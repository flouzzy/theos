<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Course;
use App\Entity\Module;
use App\Entity\Lesson;
use App\Entity\PushSubscription;
use App\Repository\CourseRepository;
use App\Service\JWT;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;

#[Route('/api', name: 'api_')]
#[RateLimiter('api')]
class ApiController extends AbstractController
{
    private function checkAuth(Request $request, JWT $jwt, EntityManagerInterface $entityManager): ?User
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        if (!$jwt->isValid($token)) {
            return null;
        }

        $payload = $jwt->getPayload($token);
        if (!isset($payload['user_id'])) {
            return null;
        }

        $user = $entityManager->getRepository(User::class)->find($payload['user_id']);

        if (!$user || !$jwt->check($token, (string) $user->getJwtSecret())) {
            return null;
        }

        if ($jwt->isExpired($token)) {
            return null;
        }

        return $user;
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWT $jwt
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password required'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Generate or get JWT secret for user
        if (!$user->getJwtSecret()) {
            $user->setJwtSecret(base64_encode(random_bytes(32)));
            $entityManager->flush();
        }

        $token = $jwt->generate(
            ['typ' => 'JWT', 'alg' => 'HS256'],
            ['user_id' => $user->getId(), 'email' => $user->getEmail()],
            (string) $user->getJwtSecret()
        );

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
            ]
        ]);
    }

    #[Route('/courses', name: 'courses', methods: ['GET'])]
    public function courses(Request $request, JWT $jwt, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->checkAuth($request, $jwt, $entityManager);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $courses = $user->getCourses();
        $data = [];
        foreach ($courses as $course) {
            $data[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'slug' => $course->getSlug(),
                'excerpt' => $course->getExcerpt(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/courses/public', name: 'courses_public', methods: ['GET'])]
    public function publicCourses(CourseRepository $courseRepository): JsonResponse
    {
        $courses = $courseRepository->findBy(['visibility' => 'public', 'status' => 'published']);
        $data = [];
        foreach ($courses as $course) {
            $data[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'slug' => $course->getSlug(),
                'excerpt' => $course->getExcerpt(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/course/{slug}/modules', name: 'course_modules', methods: ['GET'])]
    public function courseModules(string $slug, Request $request, JWT $jwt, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->checkAuth($request, $jwt, $entityManager);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $course = $entityManager->getRepository(Course::class)->findOneBy(['slug' => $slug]);
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        foreach ($course->getModules() as $module) {
            $data[] = [
                'id' => $module->getId(),
                'title' => $module->getTitle(),
                'slug' => $module->getSlug(),
                'lessonCount' => count($module->getLessons()),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/module/{slug}/lessons', name: 'module_lessons', methods: ['GET'])]
    public function moduleLessons(string $slug, Request $request, JWT $jwt, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->checkAuth($request, $jwt, $entityManager);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $module = $entityManager->getRepository(Module::class)->findOneBy(['slug' => $slug]);
        if (!$module) {
            return new JsonResponse(['error' => 'Module not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        foreach ($module->getLessons() as $lesson) {
            $data[] = [
                'id' => $lesson->getId(),
                'title' => $lesson->getTitle(),
                'slug' => $lesson->getSlug(),
                'videoUrl' => $lesson->getVideoUrl(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/push/subscribe', name: 'push_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, JWT $jwt, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->checkAuth($request, $jwt, $entityManager);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $endpoint = $data['endpoint'] ?? null;
        $keys = $data['keys'] ?? null;

        if (!$endpoint || !$keys) {
            return new JsonResponse(['error' => 'Invalid subscription data'], Response::HTTP_BAD_REQUEST);
        }

        // Avoid duplicates
        $repo = $entityManager->getRepository(PushSubscription::class);
        $existing = $repo->findOneBy(['endpoint' => $endpoint]);
        
        if ($existing) {
            return new JsonResponse(['success' => true, 'message' => 'Already subscribed']);
        }

        $subscription = new PushSubscription();
        $subscription->setUser($user);
        $subscription->setEndpoint($endpoint);
        $subscription->setKeys($keys);

        $entityManager->persist($subscription);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
