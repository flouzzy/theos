<?php

namespace App\Tests\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\Note;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NoteControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->passwordHasher = static::getContainer()->get('security.user_password_hasher');

        // Clean up database tables involved in Note tests
        $this->entityManager->createQuery('DELETE FROM App\Entity\Notification')->execute();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM note_likes');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Note')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Lesson')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Module')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Course')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    private function createUser(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createCourseAndLesson(): array
    {
        $course = new Course();
        $course->setTitle('Test Course');

        $module = new Module();
        $module->setTitle('Test Module');
        $module->addCourse($course);

        $lesson = new Lesson();
        $lesson->setTitle('Test Lesson');
        $lesson->setModule($module);

        $this->entityManager->persist($course);
        $this->entityManager->persist($module);
        $this->entityManager->persist($lesson);
        $this->entityManager->flush();

        return ['course' => $course, 'module' => $module, 'lesson' => $lesson];
    }

    private function createNote(User $user, Lesson $lesson, string $content = 'My note content'): Note
    {
        $note = new Note();
        $note->setUser($user);
        $note->setLesson($lesson);
        $note->setContent($content);

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        return $note;
    }

    public function testIndexRequiresAuth(): void
    {
        $this->client->request('GET', '/note/');
        $this->assertResponseRedirects('/login');
    }

    public function testIndexListsUserNotes(): void
    {
        $user = $this->createUser('user1@example.com', 'password');
        $this->client->loginUser($user);

        $data = $this->createCourseAndLesson();
        $this->createNote($user, $data['lesson'], 'Note content 1');

        $this->client->request('GET', '/note/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'Note content 1');
    }

    public function testShowNote(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->client->loginUser($user);

        $data = $this->createCourseAndLesson();
        $note = $this->createNote($user, $data['lesson'], 'Show my note');

        $this->client->request('GET', '/note/' . $note->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'Show my note');
    }

    public function testIndexByLesson(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->client->loginUser($user);

        $data = $this->createCourseAndLesson();
        $this->createNote($user, $data['lesson'], 'Lesson specific note');

        $this->client->request('GET', '/note/lesson/' . $data['lesson']->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'Lesson specific note');
    }

    public function testShowOrAddSubmitsForm(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->client->loginUser($user);

        $data = $this->createCourseAndLesson();

        $url = sprintf('/note/%s/%s/%s', $data['course']->getSlug(), $data['module']->getSlug(), $data['lesson']->getId());
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauvegarder')->form();
        $form['note[content]'] = 'My new cool note';

        $this->client->submit($form);

        $this->assertResponseRedirects(sprintf('/lesson/%s/%s/%s', $data['course']->getSlug(), $data['module']->getSlug(), $data['lesson']->getId()));

        $notes = $this->entityManager->getRepository(Note::class)->findAll();
        $this->assertCount(1, $notes);
        $this->assertSame('My new cool note', $notes[0]->getContent());
        $this->assertSame($user->getId(), $notes[0]->getUser()->getId());
    }

    public function testEditNote(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->client->loginUser($user);

        $data = $this->createCourseAndLesson();
        $note = $this->createNote($user, $data['lesson'], 'Old content');

        $crawler = $this->client->request('GET', '/note/' . $note->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Mettre à jour')->form();
        $form['note[content]'] = 'Updated content';

        $this->client->submit($form);
        $this->assertResponseRedirects('/note/');

        $updatedNote = $this->entityManager->getRepository(Note::class)->find($note->getId());
        $this->assertSame('Updated content', $updatedNote->getContent());
    }

    public function testDeleteNote(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->client->loginUser($user);

        $data = $this->createCourseAndLesson();
        $note = $this->createNote($user, $data['lesson'], 'To be deleted');

        // Extract CSRF token by accessing edit or list. But we can just use the token generation inside test
        $csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')->getToken('delete' . $note->getId())->getValue();

        $this->client->request('POST', '/note/' . $note->getId(), [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/note/');

        $deletedNote = $this->entityManager->getRepository(Note::class)->find($note->getId());
        $this->assertNull($deletedNote);
    }

    public function testLikeNoteAndMilestoneNotification(): void
    {
        $owner = $this->createUser('owner@example.com', 'password');
        $data = $this->createCourseAndLesson();
        $note = $this->createNote($owner, $data['lesson'], 'Awesome note');

        $liker1 = $this->createUser('liker1@example.com', 'password');

        $this->client->loginUser($liker1);

        $csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')->getToken('like_note' . $note->getId())->getValue();

        $this->client->request('POST', '/note/' . $note->getId() . '/like', [
            '_token' => $csrfToken,
        ], [], ['HTTP_REFERER' => '/note/']);

        $this->assertResponseRedirects('/note/');

        $this->entityManager->refresh($note);
        $this->assertCount(1, $note->getLikes());

        // Create 4 more likes to hit the milestone
        for ($i = 2; $i <= 5; $i++) {
            $liker = $this->createUser("liker$i@example.com", 'password');
            $this->client->loginUser($liker);

            $csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')->getToken('like_note' . $note->getId())->getValue();

            $this->client->request('POST', '/note/' . $note->getId() . '/like', [
                '_token' => $csrfToken,
            ], [], ['HTTP_REFERER' => '/note/']);
        }

        $this->entityManager->refresh($note);
        $this->assertCount(5, $note->getLikes());

        // Check if notification was created for the owner
        $notifications = $this->entityManager->getRepository(\App\Entity\Notification::class)->findBy(['user' => $owner]);
        $this->assertCount(1, $notifications);
        $this->assertSame('🎉 Ta note est populaire !', $notifications[0]->getTitle());
    }
}
