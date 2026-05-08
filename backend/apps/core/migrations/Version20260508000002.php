<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige columna uuid de core_user_smtp_config: VARCHAR(22) → BINARY(16)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_user_smtp_config MODIFY uuid BINARY(16) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_user_smtp_config MODIFY uuid VARCHAR(22) NOT NULL');
    }
}
