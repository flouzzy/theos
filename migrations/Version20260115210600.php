<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115210600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment_user (comment_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (comment_id, user_id))');
        $this->addSql('CREATE INDEX IDX_ABA574A5F8697D13 ON comment_user (comment_id)');
        $this->addSql('CREATE INDEX IDX_ABA574A5A76ED395 ON comment_user (user_id)');
        $this->addSql('ALTER TABLE comment_user ADD CONSTRAINT FK_ABA574A5F8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_user ADD CONSTRAINT FK_ABA574A5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD lesson_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id)');
        $this->addSql('CREATE INDEX IDX_9474526CCDF80196 ON comment (lesson_id)');
        $this->addSql('ALTER TABLE completion ADD score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE module_completion ADD score DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment_user DROP CONSTRAINT FK_ABA574A5F8697D13');
        $this->addSql('ALTER TABLE comment_user DROP CONSTRAINT FK_ABA574A5A76ED395');
        $this->addSql('DROP TABLE comment_user');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526CCDF80196');
        $this->addSql('DROP INDEX IDX_9474526CCDF80196');
        $this->addSql('ALTER TABLE comment DROP lesson_id');
        $this->addSql('ALTER TABLE completion DROP score');
        $this->addSql('ALTER TABLE module_completion DROP score');
    }
}
