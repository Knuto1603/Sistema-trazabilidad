<?php

namespace App\apps\security\Command;

use App\apps\security\Entity\UserRole;
use App\apps\security\Repository\UserRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-roles',
    description: 'Inicializa los roles ROLE_USER y ROLE_ADMIN',
    aliases: ['user:role:initialize', 'security:role:initialize']
)]
class InitializeRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRoleRepository $userRoleRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Inicializando Roles del Sistema');

        try {

            // Roles a crear
            $roles = [
                ['name' => 'ROLE_USER', 'alias' => 'User'],
                ['name' => 'ROLE_ADMIN', 'alias' => 'Admin']
            ];

            $createdRoles = 0;

            foreach ($roles as $roleData) {
                // Verificar si el rol ya existe
                $existingRole = $this->userRoleRepository->findOneBy(['name' => $roleData['name']]);

                if (!$existingRole) {
                    // Crear nuevo rol;
                    $role = new UserRole();
                    $role->setName($roleData['name']);
                    $role->setAlias($roleData['alias']);

                    $this->entityManager->persist($role);
                    $createdRoles++;

                    $io->text("✓ Rol creado: {$roleData['name']} - {$roleData['alias']}");
                } else {
                    $io->text("- Rol ya existe: {$roleData['name']}");
                }
            }

            if ($createdRoles > 0) {
                $this->entityManager->flush();
                $io->success("Se crearon {$createdRoles} roles exitosamente");
            } else {
                $io->info('Todos los roles ya existen en el sistema');
            }

        } catch (\Exception $e) {
            $io->error('Error al inicializar roles: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
