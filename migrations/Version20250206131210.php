<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250206131210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cohort (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, year SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cohort_course (cohort_id INT NOT NULL, course_id INT NOT NULL, INDEX IDX_A8FE089635983C93 (cohort_id), INDEX IDX_A8FE0896591CC992 (course_id), PRIMARY KEY(cohort_id, course_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cohort_course ADD CONSTRAINT FK_A8FE089635983C93 FOREIGN KEY (cohort_id) REFERENCES cohort (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cohort_course ADD CONSTRAINT FK_A8FE0896591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE badge ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cohort_course DROP FOREIGN KEY FK_A8FE089635983C93');
        $this->addSql('ALTER TABLE cohort_course DROP FOREIGN KEY FK_A8FE0896591CC992');
        $this->addSql('DROP TABLE cohort');
        $this->addSql('DROP TABLE cohort_course');
        $this->addSql('ALTER TABLE badge DROP created_at');
    }
}
