<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columna fecha_despacho (nullable) a core_despacho';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_despacho ADD COLUMN fecha_despacho DATE NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_despacho DROP COLUMN fecha_despacho');
    }
}
