<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240302161807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE course_completion (id INT AUTO_INCREMENT NOT NULL, course_id INT DEFAULT NULL, user_id INT DEFAULT NULL, completed TINYINT(1) DEFAULT NULL, INDEX IDX_E73479FD591CC992 (course_id), INDEX IDX_E73479FDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE module_completion (id INT AUTO_INCREMENT NOT NULL, module_id INT NOT NULL, user_id INT DEFAULT NULL, completed TINYINT(1) DEFAULT NULL, INDEX IDX_AD331CE3AFC2B591 (module_id), INDEX IDX_AD331CE3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE course_completion ADD CONSTRAINT FK_E73479FD591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_completion ADD CONSTRAINT FK_E73479FDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE module_completion ADD CONSTRAINT FK_AD331CE3AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE module_completion ADD CONSTRAINT FK_AD331CE3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_completion DROP FOREIGN KEY FK_E73479FD591CC992');
        $this->addSql('ALTER TABLE course_completion DROP FOREIGN KEY FK_E73479FDA76ED395');
        $this->addSql('ALTER TABLE module_completion DROP FOREIGN KEY FK_AD331CE3AFC2B591');
        $this->addSql('ALTER TABLE module_completion DROP FOREIGN KEY FK_AD331CE3A76ED395');
        $this->addSql('DROP TABLE course_completion');
        $this->addSql('DROP TABLE module_completion');
    }
}
