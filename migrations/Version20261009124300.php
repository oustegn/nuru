<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Renommage sans recréation des comptes ni modification de leurs autorisations. */
final class Version20261009124300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renommer nuru_user en user, indépendamment de la marque, en conservant comptes et affectations.';
    }

    public function isTransactional(): bool
    {
        // Le DDL MySQL effectue un commit implicite.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof MySQLPlatform, 'Cette migration cible MySQL.');
        $this->abortIf(!$schema->hasTable('nuru_user'), 'La table source nuru_user est absente. Vérifiez les migrations précédentes et tout renommage manuel.');
        $this->abortIf($schema->hasTable('user'), 'La table user existe déjà. Aucun écrasement ni fusion automatique ne sera effectué.');
        $this->addSql('RENAME TABLE `nuru_user` TO `user`');
        $this->addSql('ALTER TABLE `user` RENAME INDEX `UNIQ_A094DD53E7927C74` TO `UNIQ_8D93D649E7927C74`');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof MySQLPlatform, 'Cette migration cible MySQL.');
        $this->abortIf(!$schema->hasTable('user'), 'La table source user est absente.');
        $this->abortIf($schema->hasTable('nuru_user'), 'La table nuru_user existe déjà. Aucun écrasement ne sera effectué.');
        $this->addSql('ALTER TABLE `user` RENAME INDEX `UNIQ_8D93D649E7927C74` TO `UNIQ_A094DD53E7927C74`');
        $this->addSql('RENAME TABLE `user` TO `nuru_user`');
    }
}
