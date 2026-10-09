<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Inscription publique : champs d’activation, audit de décision et jetons de vérification.
 */
final class Version20261009142021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter les inscriptions publiques et leur vérification sans modifier les accès des comptes existants.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform, 'Cette migration cible MySQL.');
        $this->addSql('ALTER TABLE etablissement ADD adresse LONGTEXT DEFAULT NULL, ADD ville VARCHAR(100) DEFAULT NULL, ADD site_web VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD type_inscription VARCHAR(20) DEFAULT \'interne\' NOT NULL, ADD statut_validation VARCHAR(20) DEFAULT \'approuve\' NOT NULL, ADD email_verifie_le DATETIME DEFAULT NULL, ADD decision_le DATETIME DEFAULT NULL, ADD motif_refus LONGTEXT DEFAULT NULL, ADD specialite VARCHAR(180) DEFAULT NULL, ADD presentation LONGTEXT DEFAULT NULL, ADD telephone VARCHAR(30) DEFAULT NULL, ADD verification_token_hash VARCHAR(64) DEFAULT NULL, ADD verification_expire_le DATETIME DEFAULT NULL, ADD verification_envoye_le DATETIME DEFAULT NULL, ADD decision_par_id INT DEFAULT NULL, ADD etablissement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6493F7F7BC9 FOREIGN KEY (decision_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649FF631228 FOREIGN KEY (etablissement_id) REFERENCES etablissement (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649282BDDD4 ON `user` (verification_token_hash)');
        $this->addSql('CREATE INDEX IDX_8D93D6493F7F7BC9 ON `user` (decision_par_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649FF631228 ON `user` (etablissement_id)');
    }

    public function down(Schema $schema): void
    {
        // Le rollback retire les données de vérification et de décision ; ne pas l’utiliser comme procédure de mise à jour.
        $this->addSql('ALTER TABLE etablissement DROP adresse, DROP ville, DROP site_web');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6493F7F7BC9');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649FF631228');
        $this->addSql('DROP INDEX UNIQ_8D93D649282BDDD4 ON `user`');
        $this->addSql('DROP INDEX IDX_8D93D6493F7F7BC9 ON `user`');
        $this->addSql('DROP INDEX IDX_8D93D649FF631228 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP type_inscription, DROP statut_validation, DROP email_verifie_le, DROP decision_le, DROP motif_refus, DROP specialite, DROP presentation, DROP telephone, DROP verification_token_hash, DROP verification_expire_le, DROP verification_envoye_le, DROP decision_par_id, DROP etablissement_id');
    }
}
