<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ampliar campo name de core_parametro a TEXT para soportar cadenas largas (ej: múltiples emails CC)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_parametro MODIFY name LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_parametro MODIFY name VARCHAR(100) NOT NULL');
    }
}
