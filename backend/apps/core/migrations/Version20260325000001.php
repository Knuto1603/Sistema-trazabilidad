<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega campo destino a core_factura';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE core_factura ADD destino VARCHAR(100) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE core_factura DROP COLUMN destino");
    }
}
