<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columna modules (JSON) a security_user_role con módulos por defecto para roles existentes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE security_user_role ADD modules JSON NOT NULL DEFAULT ('[]')");

        $allModules = json_encode([
            'dashboard', 'productores', 'senasa', 'despachos', 'clientes',
            'tipo_cambio', 'reporte', 'cuentas_cobrar', 'usuarios', 'configuracion', 'developer',
        ]);
        $knutoModules = json_encode([
            'dashboard', 'productores', 'senasa', 'despachos', 'clientes',
            'tipo_cambio', 'reporte', 'cuentas_cobrar', 'developer',
        ]);
        $userModules = json_encode(['dashboard']);

        $this->addSql("UPDATE security_user_role SET modules = '{$allModules}' WHERE name = 'ROLE_ADMIN'");
        $this->addSql("UPDATE security_user_role SET modules = '{$knutoModules}' WHERE name = 'ROLE_KNUTO'");
        $this->addSql("UPDATE security_user_role SET modules = '{$userModules}' WHERE name = 'ROLE_USER'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE security_user_role DROP COLUMN modules');
    }
}
