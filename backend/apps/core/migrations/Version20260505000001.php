<?php

declare(strict_types=1);

namespace App\apps\core\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega cliente_factura_id a core_factura para permitir facturar a un RUC diferente al del despacho';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_factura ADD COLUMN cliente_factura_id INT NULL DEFAULT NULL');
        $this->addSql('ALTER TABLE core_factura ADD CONSTRAINT FK_factura_cliente_factura FOREIGN KEY (cliente_factura_id) REFERENCES core_cliente(id)');
        $this->addSql('CREATE INDEX IDX_factura_cliente_factura ON core_factura (cliente_factura_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_factura DROP FOREIGN KEY FK_factura_cliente_factura');
        $this->addSql('DROP INDEX IDX_factura_cliente_factura ON core_factura');
        $this->addSql('ALTER TABLE core_factura DROP COLUMN cliente_factura_id');
    }
}
