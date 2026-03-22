<?php

namespace App\apps\security\Command;


use App\apps\security\Entity\User;
use App\apps\security\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use function Symfony\Component\String\u;

#[AsCommand(
    name: 'app:user:create',
    description: 'Create user',
    aliases: ['user:create']
)]
class UserCreateCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the new user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The plain password of the new user')
            ->addArgument('full-name', InputArgument::OPTIONAL, 'The full name of the new user')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'If set, the user is created as an administrator');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('username')
            && null !== $input->getArgument('password')
            && null !== $input->getArgument('full-name')) {
            return;
        }

        $this->io->title('Create Dto Command Interactive Wizard');

        // Ask for the username if it's not defined
        $username = $input->getArgument('username');
        if (null !== $username) {
            $this->io->text(' > <info>Username</info>: '.$username);
        } else {
            $username = $this->io->ask('Username', null, $this->validateUsername(...));
            $input->setArgument('username', $username);
        }

        // Ask for the password if it's not defined
        /** @var string|null $password */
        $password = $input->getArgument('password');

        if (null !== $password) {
            $this->io->text(' > <info>Password</info>: '.u('*')->repeat(u($password)->length()));
        } else {
            $password = $this->io->askHidden('Password (your type will be hidden)', $this->validatePassword(...));
            $input->setArgument('password', $password);
        }

        // Ask for the full name if it's not defined
        $fullName = $input->getArgument('full-name');
        if (null !== $fullName) {
            $this->io->text(' > <info>Full Name</info>: '.$fullName);
        } else {
            $fullName = $this->io->ask('Full Name', null, $this->validateFullName(...));
            $input->setArgument('full-name', $fullName);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('add-user-command');

        /** @var string $username */
        $username = $input->getArgument('username');

        /** @var string $plainPassword */
        $plainPassword = $input->getArgument('password');

        /** @var string $fullName */
        $fullName = $input->getArgument('full-name');

        $isAdmin = $input->getOption('admin');

        // make sure to validate the user data is correct
        $this->validateUserData($username, $plainPassword, $fullName);

        // create the user and hash its password
        $user = new User();
        $user->setFullName($fullName);
        $user->setUsername($username);
        $user->setRoles([$isAdmin ? User::ROLE_ADMIN : User::ROLE_USER]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success(\sprintf('%s was successfully created: %s (%s)', $isAdmin ? 'Administrator user' : 'Dto', $user->getUsername(), $user->getFullName()));

        $event = $stopwatch->stop('add-user-command');
        if ($output->isVerbose()) {
            $this->io->comment(\sprintf('New user database id: %d / Elapsed time: %.2f ms / Consumed memory: %.2f MB', $user->getId(), $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return Command::SUCCESS;
    }

    private function validateUsername(?string $username): string
    {
        if (empty($username)) {
            throw new \InvalidArgumentException('The username can not be empty.');
        }

        if (1 !== preg_match('/^[a-z_]+$/', $username)) {
            throw new \InvalidArgumentException('The username must contain only lowercase latin characters and underscores.');
        }

        return $username;
    }

    private function validatePassword(?string $plainPassword): string
    {
        if (empty($plainPassword)) {
            throw new \InvalidArgumentException('The password can not be empty.');
        }

        if (u($plainPassword)->trim()->length() < 6) {
            throw new \InvalidArgumentException('The password must be at least 6 characters long.');
        }

        return $plainPassword;
    }

    private function validateFullName(?string $fullName): string
    {
        if (empty($fullName)) {
            throw new \InvalidArgumentException('The full name can not be empty.');
        }

        return $fullName;
    }

    private function validateUserData(string $username, string $plainPassword, string $fullName): void
    {
        // first check if a user with the same username already exists.
        $existingUser = $this->userRepository->findOneBy(['username' => $username]);

        if (null !== $existingUser) {
            throw new \RuntimeException(\sprintf('There is already a user registered with the "%s" username.', $username));
        }

        // validate password and email if is not this input means interactive.
        $this->validatePassword($plainPassword);
        $this->validateFullName($fullName);
    }
}
