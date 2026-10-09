<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Schéma initial MySQL : établissements, formations et modules.
 */
final class Version20261009092002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Créer les établissements, formations et modules, avec leurs relations et contraintes uniques.';
    }

    public function isTransactional(): bool
    {
        // MySQL effectue des commits implicites pour CREATE/ALTER TABLE.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform, 'Cette migration cible MySQL.');
        $this->addSql('CREATE TABLE etablissement (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, cree_le DATETIME NOT NULL, UNIQUE INDEX UNIQ_20FD592C989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, titre VARCHAR(255) NOT NULL, domaine VARCHAR(64) NOT NULL, programme VARCHAR(32) NOT NULL, modalite VARCHAR(32) NOT NULL, duree VARCHAR(64) NOT NULL, heures INT NOT NULL, prix INT NOT NULL, image VARCHAR(512) NOT NULL, formateur VARCHAR(255) NOT NULL, niveau VARCHAR(64) NOT NULL, description LONGTEXT NOT NULL, objectifs JSON NOT NULL, mise_en_avant TINYINT NOT NULL, statut VARCHAR(64) NOT NULL, lieu VARCHAR(255) DEFAULT NULL, dates VARCHAR(255) DEFAULT NULL, places INT DEFAULT NULL, cree_le DATETIME NOT NULL, etablissement_id INT NOT NULL, UNIQUE INDEX UNIQ_404021BF989D9B62 (slug), INDEX IDX_404021BFFF631228 (etablissement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE module_formation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, position INT NOT NULL, formation_id INT NOT NULL, UNIQUE INDEX uniq_module_formation_position (formation_id, position), INDEX IDX_1A213E775200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BFFF631228 FOREIGN KEY (etablissement_id) REFERENCES etablissement (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE module_formation ADD CONSTRAINT FK_1A213E775200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Attention : un rollback de cette migration efface les trois tables.
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BFFF631228');
        $this->addSql('ALTER TABLE module_formation DROP FOREIGN KEY FK_1A213E775200282E');
        $this->addSql('DROP TABLE etablissement');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE module_formation');
    }
}
