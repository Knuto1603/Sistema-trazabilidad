<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250322000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tabla de union ManyToMany entre security_user y security_user_role';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS security_user_user_role (
                user_id INT UNSIGNED NOT NULL,
                user_role_id INT NOT NULL,
                PRIMARY KEY (user_id, user_role_id),
                INDEX IDX_USER (user_id),
                INDEX IDX_ROLE (user_role_id),
                CONSTRAINT FK_SUR_USER FOREIGN KEY (user_id) REFERENCES security_user (id) ON DELETE CASCADE,
                CONSTRAINT FK_SUR_ROLE FOREIGN KEY (user_role_id) REFERENCES security_user_role (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS security_user_user_role');
    }
}
