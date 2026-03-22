<?php

namespace App\apps\security\Command;

use App\apps\security\Entity\User;
use App\apps\security\Entity\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-complete',
    description: 'Crea el UserRole ROLE_ADMIN y el usuario administrador completo',
    aliases: ['user:initialize', 'security:user:initialize']
)]
class CreateAdminCompleteCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Creando UserRole ROLE_ADMIN y Usuario Administrador');

        try {
            // Paso 1: Crear el UserRole ROLE_ADMIN
            $userRoleRepository = $this->entityManager->getRepository(UserRole::class);
            $adminRole = $userRoleRepository->findOneBy(['name' => 'KNUTO_ROLE']);

            if (!$adminRole) {
                $adminRole = new UserRole();
                $adminRole->setName('KNUTO_ROLE');
                $adminRole->setAlias('Knuto');

                $this->entityManager->persist($adminRole);
                $this->entityManager->flush();

                $io->success('✓ UserRole ROLE_ADMIN creado exitosamente');
            } else {
                $io->info('- El UserRole ROLE_ADMIN ya existe');
            }

            // Paso 2: Verificar si ya existe un usuario admin
            $userRepository = $this->entityManager->getRepository(User::class);
            $adminUser = $userRepository->findOneBy(['username' => 'admin']);

            if (!$adminUser) {
                // Paso 3: Crear el usuario admin
                $adminUser = new User();
                $adminUser->setUsername('admin');
                $adminUser->setFullName('Administrador del Sistema');

                // Hash de la contraseña
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $adminUser,
                    'admin123'
                );
                $adminUser->setPassword($hashedPassword);

                // Paso 4: Asignar el rol ROLE_ADMIN al usuario
                $adminUser->setRoles([$userRoleRepository->findOneBy(['name' => 'KNUTO_ROLE'])->uuidToString()]);

                $this->entityManager->persist($adminUser);
                $this->entityManager->flush();

                $io->success('✓ Usuario admin creado exitosamente');
                $io->success('✓ Rol ROLE_K/ADMIN asignado al usuario admin');

                $io->section('Credenciales del Administrador:');
                $io->text([
                    'Username: admin',
                    'Full Name: Administrador del Sistema',
                    'Password: admin123',
                    'Roles: ROLE_ADMIN, ROLE_USER'
                ]);

            } else {
                $io->info('- El usuario admin ya existe');

                // Verificar si ya tiene el rol ROLE_ADMIN asignado
                $currentRoles = $adminUser->getRoles();
                if (!in_array(User::ROLE_ADMIN, $currentRoles)) {
                    $roles = array_unique(array_merge($currentRoles, [User::ROLE_ADMIN]));
                    $adminUser->setRoles($roles);
                    $this->entityManager->flush();
                    $io->success('✓ Rol ROLE_ADMIN asignado al usuario admin existente');
                } else {
                    $io->info('- El usuario admin ya tiene el rol ROLE_ADMIN asignado');
                }
            }

            $io->success('Proceso completado exitosamente');

            $io->warning([
                'IMPORTANTE:',
                '- Cambia la contraseña por defecto en producción',
                '- El usuario está activo y listo para usar',
                '- UserRole creado para catalogar roles disponibles'
            ]);

        } catch (\Exception $e) {
            $io->error('Error al crear admin: ' . $e->getMessage());
            $io->error('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
