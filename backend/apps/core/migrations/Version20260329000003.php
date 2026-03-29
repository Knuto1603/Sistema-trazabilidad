<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260329000003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_despacho ADD COLUMN operacion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_despacho ADD CONSTRAINT FK_despacho_operacion FOREIGN KEY (operacion_id) REFERENCES core_operacion (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_despacho DROP FOREIGN KEY FK_despacho_operacion');
        $this->addSql('ALTER TABLE core_despacho DROP COLUMN operacion_id');
    }
}
