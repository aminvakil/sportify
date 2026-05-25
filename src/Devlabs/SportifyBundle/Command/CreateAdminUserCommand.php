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

class CreateAdminUserCommand extends Command
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
            ->setName('sportify:user:create-admin')
            ->setDescription('Create the initial admin user')
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email address')
            ->addArgument('username', InputArgument::OPTIONAL, 'Admin username. Defaults to the email address.')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Admin password. If omitted, the command asks for it interactively.')
            ->addOption('slack-username', null, InputOption::VALUE_REQUIRED, 'Admin Slack username.', 'slack_username')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->adminUserExists()) {
            $output->writeln('<error>An admin user already exists.</error>');

            return 1;
        }

        $email = trim((string) $input->getArgument('email'));
        $username = trim((string) ($input->getArgument('username') ?: $email));
        $password = $input->getOption('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output->writeln('<error>Please provide a valid email address.</error>');

            return 1;
        }

        if ($username === '') {
            $output->writeln('<error>Please provide a username.</error>');

            return 1;
        }

        if ($password === null) {
            if (!$input->isInteractive()) {
                $output->writeln('<error>Please provide --password when running non-interactively.</error>');

                return 1;
            }

            $question = new Question('Admin password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $this->getHelper('question')->ask($input, $output, $question);
        }

        if ($password === '') {
            $output->writeln('<error>Please provide a password.</error>');

            return 1;
        }

        $canonicalEmail = $this->canonicalize($email);
        $canonicalUsername = $this->canonicalize($username);

        if ($this->userExists($canonicalEmail, $canonicalUsername)) {
            $output->writeln('<error>A user with this username or email already exists.</error>');

            return 1;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setEmailCanonical($canonicalEmail);
        $user->setUsername($username);
        $user->setUsernameCanonical($canonicalUsername);
        $user->setSlackUsername($input->getOption('slack-username'));
        $user->setEnabled(true);
        $user->addRole('ROLE_ADMIN');
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(sprintf('<info>Admin user <comment>%s</comment> was created.</info>', $email));

        return 0;
    }

    private function adminUserExists()
    {
        foreach ($this->em->getRepository(User::class)->findAll() as $user) {
            if ($user->hasRole('ROLE_ADMIN') || $user->hasRole(User::ROLE_SUPER_ADMIN)) {
                return true;
            }
        }

        return false;
    }

    private function userExists($email, $username)
    {
        return null !== $this->em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.emailCanonical = :email')
            ->orWhere('u.usernameCanonical = :username')
            ->setParameter('email', $email)
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function canonicalize($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }
}
