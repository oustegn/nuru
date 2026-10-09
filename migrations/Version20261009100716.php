<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Extension additive : comptes locaux et affectation optionnelle des formateurs.
 */
final class Version20261009100716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Créer les utilisateurs et relier facultativement les formations à un formateur ; conserver les données existantes.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform, 'Cette migration cible MySQL.');
        $this->addSql('CREATE TABLE nuru_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, prenom VARCHAR(100) NOT NULL, nom VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, actif TINYINT NOT NULL, cree_le DATETIME NOT NULL, UNIQUE INDEX UNIQ_A094DD53E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE formation ADD formateur_utilisateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BF545D43BA FOREIGN KEY (formateur_utilisateur_id) REFERENCES nuru_user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_404021BF545D43BA ON formation (formateur_utilisateur_id)');
    }

    public function down(Schema $schema): void
    {
        // Attention : ce rollback supprime les comptes et leurs affectations.
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BF545D43BA');
        $this->addSql('DROP INDEX IDX_404021BF545D43BA ON formation');
        $this->addSql('ALTER TABLE formation DROP formateur_utilisateur_id');
        $this->addSql('DROP TABLE nuru_user');
    }
}
