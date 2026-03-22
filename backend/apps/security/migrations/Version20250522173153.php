<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250522173153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE security_user (id INT UNSIGNED AUTO_INCREMENT NOT NULL, photo_id INT DEFAULT NULL, username VARCHAR(30) NOT NULL, roles JSON NOT NULL COMMENT '(DC2Type:json)', password VARCHAR(100) NOT NULL, full_name VARCHAR(100) DEFAULT NULL, gender BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)', uuid BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_52825A88F85E0677 (username), UNIQUE INDEX UNIQ_52825A88D17F50A6 (uuid), UNIQUE INDEX UNIQ_52825A887E9E4C8C (photo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE security_user_role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, alias VARCHAR(100) NOT NULL, uuid BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_45561EDCD17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE security_user ADD CONSTRAINT FK_52825A887E9E4C8C FOREIGN KEY (photo_id) REFERENCES attach_file (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE security_user DROP FOREIGN KEY FK_52825A887E9E4C8C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE security_user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE security_user_role
        SQL);
    }
}
