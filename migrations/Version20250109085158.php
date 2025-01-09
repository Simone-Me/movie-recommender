<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250109085158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_1D5EF26F55BCC5E5 ON movie');
        $this->addSql('ALTER TABLE movie DROP tmdb_id, CHANGE id id INT NOT NULL, CHANGE poster_path poster_path VARCHAR(255) DEFAULT NULL, CHANGE release_date release_date DATE DEFAULT NULL, CHANGE vote_average vote_average NUMERIC(3, 1) DEFAULT NULL, CHANGE revenue revenue NUMERIC(20, 2) DEFAULT NULL, CHANGE genres genres JSON DEFAULT NULL, CHANGE original_language original_language VARCHAR(255) DEFAULT NULL, CHANGE production_countries production_countries JSON DEFAULT NULL, CHANGE budget budget NUMERIC(20, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE movie ADD tmdb_id INT NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE poster_path poster_path VARCHAR(255) DEFAULT \'NULL\', CHANGE release_date release_date DATE DEFAULT \'NULL\', CHANGE vote_average vote_average NUMERIC(3, 1) DEFAULT \'NULL\', CHANGE revenue revenue NUMERIC(20, 2) DEFAULT \'NULL\', CHANGE genres genres LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE original_language original_language VARCHAR(255) DEFAULT \'NULL\', CHANGE production_countries production_countries LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE budget budget NUMERIC(20, 2) DEFAULT \'NULL\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D5EF26F55BCC5E5 ON movie (tmdb_id)');
    }
}
