<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410000002 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE core_voucher (
            id INT AUTO_INCREMENT NOT NULL,
            uuid BINARY(16) NOT NULL,
            numero VARCHAR(50) NOT NULL,
            numero_operacion VARCHAR(50) DEFAULT NULL,
            monto_total DECIMAL(12,2) NOT NULL,
            fecha DATE NOT NULL,
            cliente_id INT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY uq_voucher_uuid (uuid),
            UNIQUE KEY uq_voucher_numero_cliente (numero, cliente_id),
            INDEX idx_voucher_cliente (cliente_id),
            CONSTRAINT fk_voucher_cliente FOREIGN KEY (cliente_id) REFERENCES core_cliente (id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE core_pago_factura (
            id INT AUTO_INCREMENT NOT NULL,
            uuid BINARY(16) NOT NULL,
            voucher_id INT NOT NULL,
            factura_id INT NOT NULL,
            monto_aplicado DECIMAL(12,2) NOT NULL,
            justificante_eliminacion TEXT DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY uq_pago_uuid (uuid),
            INDEX idx_pago_voucher (voucher_id),
            INDEX idx_pago_factura (factura_id),
            CONSTRAINT fk_pago_voucher FOREIGN KEY (voucher_id) REFERENCES core_voucher (id),
            CONSTRAINT fk_pago_factura FOREIGN KEY (factura_id) REFERENCES core_factura (id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE core_pago_factura');
        $this->addSql('DROP TABLE core_voucher');
    }
}
