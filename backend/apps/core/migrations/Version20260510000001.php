<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega campos de firma y CC a core_user_smtp_config';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_user_smtp_config ADD display_name VARCHAR(150) DEFAULT NULL, ADD firma_nombre VARCHAR(150) DEFAULT NULL, ADD firma_cargo VARCHAR(100) DEFAULT NULL, ADD firma_empresa VARCHAR(150) DEFAULT NULL, ADD cc_emails LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_user_smtp_config DROP COLUMN display_name, DROP COLUMN firma_nombre, DROP COLUMN firma_cargo, DROP COLUMN firma_empresa, DROP COLUMN cc_emails');
    }
}
