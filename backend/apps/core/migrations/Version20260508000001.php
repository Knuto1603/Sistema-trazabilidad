<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tabla core_user_smtp_config para almacenar credenciales SMTP cifradas por usuario';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE core_user_smtp_config (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid BINARY(16) NOT NULL,
                user_uuid VARCHAR(22) NOT NULL,
                smtp_email VARCHAR(150) NOT NULL,
                smtp_password_encrypted VARCHAR(500) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY UNIQ_smtp_config_uuid (uuid),
                UNIQUE KEY UNIQ_smtp_config_user_uuid (user_uuid),
                KEY idx_smtp_config_user_uuid (user_uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE core_user_smtp_config');
    }
}
