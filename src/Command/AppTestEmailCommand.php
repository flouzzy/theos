<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:test-email',
    description: 'Sends a test email using the default application sender',
)]
class AppTestEmailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire(env: 'DEFAULT_FROM_EMAIL')] private string $defaultFromEmail,
        #[Autowire(env: 'DEFAULT_FROM_NAME')] private string $defaultFromName
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('to', InputArgument::REQUIRED, 'Recipient email address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = $input->getArgument('to');

        $io->info(sprintf('Sending test email from %s <%s> to %s', $this->defaultFromName, $this->defaultFromEmail, $to));

        $email = (new Email())
            ->from(new Address($this->defaultFromEmail, $this->defaultFromName))
            ->to($to)
            ->subject('Test Email from Académie')
            ->text('This is a test email sent from the application custom command.')
            ->html('<p>This is a test email sent from the application custom command.</p>');

        try {
            $this->mailer->send($email);
            $io->success('Email sent successfully!');
        } catch (\Exception $e) {
            $io->error('Failed to send email: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
