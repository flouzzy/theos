<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240120125700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course ADD author_id INT DEFAULT NULL, ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB9F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_169E6FB9F675F31B ON course (author_id)');
        $this->addSql('ALTER TABLE lesson ADD author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE lesson ADD CONSTRAINT FK_F87474F3F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_F87474F3F675F31B ON lesson (author_id)');
        $this->addSql('ALTER TABLE module ADD author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C242628F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_C242628F675F31B ON module (author_id)');
        $this->addSql('ALTER TABLE user ADD image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB9F675F31B');
        $this->addSql('DROP INDEX IDX_169E6FB9F675F31B ON course');
        $this->addSql('ALTER TABLE course DROP author_id, DROP image');
        $this->addSql('ALTER TABLE `user` DROP image');
        $this->addSql('ALTER TABLE lesson DROP FOREIGN KEY FK_F87474F3F675F31B');
        $this->addSql('DROP INDEX IDX_F87474F3F675F31B ON lesson');
        $this->addSql('ALTER TABLE lesson DROP author_id');
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C242628F675F31B');
        $this->addSql('DROP INDEX IDX_C242628F675F31B ON module');
        $this->addSql('ALTER TABLE module DROP author_id');
    }
}
