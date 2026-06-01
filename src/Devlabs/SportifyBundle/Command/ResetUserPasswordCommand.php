<?php

namespace Devlabs\SportifyBundle\Command;

use Devlabs\SportifyBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetUserPasswordCommand extends Command
{
    private $em;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setName('sportify:user:reset-password')
            ->setDescription('Reset a user password without sending email')
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'New user password. If omitted, the command asks for it interactively.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = trim((string) $input->getArgument('email'));
        $password = $input->getOption('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output->writeln('<error>Please provide a valid email address.</error>');

            return 1;
        }

        if ($password === null) {
            if (!$input->isInteractive()) {
                $output->writeln('<error>Please provide --password when running non-interactively.</error>');

                return 1;
            }

            $question = new Question('New user password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $this->getHelper('question')->ask($input, $output, $question);
        }

        if ($password === '') {
            $output->writeln('<error>Please provide a password.</error>');

            return 1;
        }

        $user = $this->em->getRepository(User::class)->findOneBy(array(
            'emailCanonical' => $this->canonicalize($email),
        ));

        if (!$user) {
            $output->writeln(sprintf('<error>User <comment>%s</comment> was not found.</error>', $email));

            return 1;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);
        $this->em->flush();

        $output->writeln(sprintf('<info>Password for user <comment>%s</comment> was reset.</info>', $user->getEmail()));

        return 0;
    }

    private function canonicalize($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }
}
