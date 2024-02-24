<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240223175600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE completion (id INT AUTO_INCREMENT NOT NULL, lesson_id INT NOT NULL, user_id INT NOT NULL, completed TINYINT(1) DEFAULT NULL, INDEX IDX_38D6377ECDF80196 (lesson_id), INDEX IDX_38D6377EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE completion ADD CONSTRAINT FK_38D6377ECDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id)');
        $this->addSql('ALTER TABLE completion ADD CONSTRAINT FK_38D6377EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE completion DROP FOREIGN KEY FK_38D6377ECDF80196');
        $this->addSql('ALTER TABLE completion DROP FOREIGN KEY FK_38D6377EA76ED395');
        $this->addSql('DROP TABLE completion');
    }
}
