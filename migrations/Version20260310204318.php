<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260310204318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE peer_review_score (id INT AUTO_INCREMENT NOT NULL, score SMALLINT NOT NULL, criterion_id INT NOT NULL, peer_review_id INT NOT NULL, INDEX IDX_E0C3DCBE97766307 (criterion_id), INDEX IDX_E0C3DCBEAE8F0C15 (peer_review_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE rubric (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE rubric_criterion (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, max_points SMALLINT NOT NULL, rubric_id INT NOT NULL, INDEX IDX_98E476F9A29EC0FC (rubric_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE peer_review_score ADD CONSTRAINT FK_E0C3DCBE97766307 FOREIGN KEY (criterion_id) REFERENCES rubric_criterion (id)');
        $this->addSql('ALTER TABLE peer_review_score ADD CONSTRAINT FK_E0C3DCBEAE8F0C15 FOREIGN KEY (peer_review_id) REFERENCES peer_review (id)');
        $this->addSql('ALTER TABLE rubric_criterion ADD CONSTRAINT FK_98E476F9A29EC0FC FOREIGN KEY (rubric_id) REFERENCES rubric (id)');
        $this->addSql('ALTER TABLE assignment ADD rubric_entity_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BA654329D5 FOREIGN KEY (rubric_entity_id) REFERENCES rubric (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_30C544BA654329D5 ON assignment (rubric_entity_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE peer_review_score DROP FOREIGN KEY FK_E0C3DCBE97766307');
        $this->addSql('ALTER TABLE peer_review_score DROP FOREIGN KEY FK_E0C3DCBEAE8F0C15');
        $this->addSql('ALTER TABLE rubric_criterion DROP FOREIGN KEY FK_98E476F9A29EC0FC');
        $this->addSql('DROP TABLE peer_review_score');
        $this->addSql('DROP TABLE rubric');
        $this->addSql('DROP TABLE rubric_criterion');
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BA654329D5');
        $this->addSql('DROP INDEX UNIQ_30C544BA654329D5 ON assignment');
        $this->addSql('ALTER TABLE assignment DROP rubric_entity_id');
    }
}
