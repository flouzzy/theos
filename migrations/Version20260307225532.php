<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260307225532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, score DOUBLE PRECISION NOT NULL, max_score DOUBLE PRECISION NOT NULL, feedback LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, cohort_id INT DEFAULT NULL, INDEX IDX_1323A575A76ED395 (user_id), INDEX IDX_1323A57535983C93 (cohort_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A57535983C93 FOREIGN KEY (cohort_id) REFERENCES cohort (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575A76ED395');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A57535983C93');
        $this->addSql('DROP TABLE evaluation');
    }
}
