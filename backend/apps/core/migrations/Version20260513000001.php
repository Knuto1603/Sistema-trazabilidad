<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tabla core_fruta_variedad para variedades por fruta';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE core_fruta_variedad (
            id INT AUTO_INCREMENT NOT NULL,
            uuid VARCHAR(255) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            fruta_id INT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_fruta_variedad_uuid (uuid),
            INDEX IDX_fruta_variedad_fruta (fruta_id),
            UNIQUE INDEX UNIQ_fruta_variedad_nombre_fruta (nombre, fruta_id),
            CONSTRAINT FK_fruta_variedad_fruta FOREIGN KEY (fruta_id) REFERENCES core_fruta (id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_fruta_variedad DROP FOREIGN KEY FK_fruta_variedad_fruta');
        $this->addSql('DROP TABLE core_fruta_variedad');
    }
}
