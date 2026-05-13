<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige tipo de columna uuid en core_fruta_variedad de VARCHAR a BINARY(16)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_fruta_variedad DROP INDEX UNIQ_fruta_variedad_uuid');
        $this->addSql('ALTER TABLE core_fruta_variedad MODIFY uuid BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE core_fruta_variedad ADD UNIQUE INDEX UNIQ_fruta_variedad_uuid (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_fruta_variedad DROP INDEX UNIQ_fruta_variedad_uuid');
        $this->addSql('ALTER TABLE core_fruta_variedad MODIFY uuid VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE core_fruta_variedad ADD UNIQUE INDEX UNIQ_fruta_variedad_uuid (uuid)');
    }
}
