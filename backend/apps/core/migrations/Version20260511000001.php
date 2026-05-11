<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega campahna_id a core_despacho y backfill por sede+fruta';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE core_despacho ADD campahna_id INT DEFAULT NULL');
        $this->connection->executeStatement('UPDATE core_despacho d JOIN core_campahna c ON d.fruta_id = c.fruta_id AND d.sede = c.sede SET d.campahna_id = c.id');

        $unmatched = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM core_despacho WHERE campahna_id IS NULL');
        $this->abortIf($unmatched > 0, sprintf(
            'Backfill incompleto: %d despachos sin campaña. Verifica que cada combinación sede+fruta tenga exactamente una campaña asociada.',
            $unmatched
        ));

        $this->addSql('ALTER TABLE core_despacho MODIFY campahna_id INT NOT NULL');
        $this->addSql('ALTER TABLE core_despacho ADD CONSTRAINT FK_core_despacho_campahna FOREIGN KEY (campahna_id) REFERENCES core_campahna (id)');
        $this->addSql('CREATE INDEX IDX_core_despacho_campahna ON core_despacho (campahna_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_despacho DROP FOREIGN KEY FK_core_despacho_campahna');
        $this->addSql('DROP INDEX IDX_core_despacho_campahna ON core_despacho');
        $this->addSql('ALTER TABLE core_despacho DROP COLUMN campahna_id');
    }
}
