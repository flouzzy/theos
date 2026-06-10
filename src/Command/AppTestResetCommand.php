<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[AsCommand(
    name: 'app:test-reset',
    description: 'Simulates a real password reset request',
)]
class AppTestResetCommand extends Command
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        #[Autowire(env: 'DEFAULT_FROM_EMAIL')] private string $fromEmail,
        #[Autowire(env: 'DEFAULT_FROM_NAME')] private string $fromName
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error('User not found');
            return Command::FAILURE;
        }

        try {
            // Clean up old requests to avoid "TooManyRequests" error
            $io->text('Cleaning up old reset requests for this user...');
            
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            
            $templatedEmail = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to((string) $user->getEmail())
                ->subject('Votre demande de réinitialisation de mot de passe')
                ->htmlTemplate('reset_password/email.html.twig')
                ->context([
                    'resetToken' => $resetToken,
                ]);

            $this->mailer->send($templatedEmail);
            $io->success('Reset password email triggered for ' . $email);
        } catch (\Throwable $e) {
            $io->error('Exception Type: ' . get_class($e));
            $io->error('Error Message: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
