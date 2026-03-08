<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260308172116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD current_cohort_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649EEBE8D3F FOREIGN KEY (current_cohort_id) REFERENCES cohort (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649EEBE8D3F ON user (current_cohort_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649EEBE8D3F');
        $this->addSql('DROP INDEX IDX_8D93D649EEBE8D3F ON `user`');
        $this->addSql('ALTER TABLE `user` DROP current_cohort_id');
    }
}
