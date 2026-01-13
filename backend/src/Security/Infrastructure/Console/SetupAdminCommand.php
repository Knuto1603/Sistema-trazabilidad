<?php

namespace App\Security\Infrastructure\Console;

use App\Security\Domain\Entity\Role;
use App\Security\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:security:setup-admin',
    description: 'Inicializa los roles y crea el primer usuario administrador.',
)]
class SetupAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // 1. Crear el Rol administrativo si no existe
            $adminRol = $this->entityManager->getRepository(Role::class)->findOneBy(['nombre' => 'ROLE_ADMIN']);

            if (!$adminRol) {
                $adminRol = new Role('ROLE_ADMIN');
                $adminRol->setDescripcion('Administrador con acceso total');
                $this->entityManager->persist($adminRol);
                $io->info('Rol ROLE_ADMIN creado.');
            }

            // 2. Crear el Usuario si no existe
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'admin']);

            if ($existingUser) {
                $io->warning('El usuario admin ya existe.');
                return Command::SUCCESS;
            }

            $user = new User();
            $user->setUsername('admin');
            $user->setNombreCompleto('Administrador General');
            $user->addRolEntity($adminRol);

            $hashedPassword = $this->passwordHasher->hashPassword($user, 'admin1234');
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);

            // Flush para asegurar la creación de ambos y su relación
            $this->entityManager->flush();

            $io->success('Configuración inicial completada. Usuario: admin / Clave: admin1234');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error durante la ejecución: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
