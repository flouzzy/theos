<?php
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require 'vendor/autoload.php';

// Note: This is a hacky way to run a command without a proper symfony command class
// Ideally, use: php bin/console make:user or create a custom command.
