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
    description: 'Crea los roles ROLE_ADMIN y KNUTO_ROLE, y el superadmin Knuto',
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

        $io->title('Inicializando roles y superadmin Knuto');

        try {
            $userRoleRepository = $this->entityManager->getRepository(UserRole::class);

            // Paso 1: Crear ROLE_ADMIN si no existe
            $adminRole = $userRoleRepository->findOneBy(['name' => 'ROLE_ADMIN']);
            if (!$adminRole) {
                $adminRole = new UserRole();
                $adminRole->setName('ROLE_ADMIN');
                $adminRole->setAlias('Admin');
                $this->entityManager->persist($adminRole);
                $this->entityManager->flush();
                $io->success('✓ UserRole ROLE_ADMIN creado');
            } else {
                $io->info('- UserRole ROLE_ADMIN ya existe');
            }

            // Paso 2: Crear KNUTO_ROLE si no existe
            $knutoRole = $userRoleRepository->findOneBy(['name' => 'KNUTO_ROLE']);
            if (!$knutoRole) {
                $knutoRole = new UserRole();
                $knutoRole->setName('KNUTO_ROLE');
                $knutoRole->setAlias('Knuto');
                $this->entityManager->persist($knutoRole);
                $this->entityManager->flush();
                $io->success('✓ UserRole KNUTO_ROLE creado');
            } else {
                $io->info('- UserRole KNUTO_ROLE ya existe');
            }

            // Paso 3: Crear o actualizar el superadmin
            $userRepository = $this->entityManager->getRepository(User::class);
            $superAdmin = $userRepository->findOneBy(['username' => 'knuto']);

            if (!$superAdmin) {
                $superAdmin = new User();
                $superAdmin->setUsername('knuto');
                $superAdmin->setFullName('Knuto');

                $hashedPassword = $this->passwordHasher->hashPassword($superAdmin, '$ergio1603');
                $superAdmin->setPassword($hashedPassword);

                $superAdmin->addRol($adminRole);
                $superAdmin->addRol($knutoRole);

                $this->entityManager->persist($superAdmin);
                $this->entityManager->flush();

                $io->success('✓ Superadmin Knuto creado exitosamente');
            } else {
                $io->info('- El usuario knuto ya existe, verificando roles...');

                $existingRoleNames = [];
                foreach ($superAdmin->getRol() as $r) {
                    $existingRoleNames[] = $r->getName();
                }

                if (!in_array('ROLE_ADMIN', $existingRoleNames)) {
                    $superAdmin->addRol($adminRole);
                    $io->success('✓ Rol ROLE_ADMIN asignado');
                }
                if (!in_array('KNUTO_ROLE', $existingRoleNames)) {
                    $superAdmin->addRol($knutoRole);
                    $io->success('✓ Rol KNUTO_ROLE asignado');
                }

                $this->entityManager->flush();
            }

            $io->section('Credenciales del Superadmin:');
            $io->text([
                'Username : knuto',
                'Full Name: Knuto',
                'Password : $ergio1603',
                'Roles    : ROLE_ADMIN, KNUTO_ROLE',
            ]);

            $io->success('Proceso completado exitosamente');

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            $io->error('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
