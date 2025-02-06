<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250206132932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE badge (id INT AUTO_INCREMENT NOT NULL, badge_type_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FEF0481DC3C8852F (badge_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE badge_user (badge_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_299D3A50F7A2C2FC (badge_id), INDEX IDX_299D3A50A76ED395 (user_id), PRIMARY KEY(badge_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE badge_type (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, code VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cohort (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, year SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cohort_course (cohort_id INT NOT NULL, course_id INT NOT NULL, INDEX IDX_A8FE089635983C93 (cohort_id), INDEX IDX_A8FE0896591CC992 (course_id), PRIMARY KEY(cohort_id, course_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, user_id INT NOT NULL, notification_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9474526C727ACA70 (parent_id), INDEX IDX_9474526CA76ED395 (user_id), INDEX IDX_9474526CEF1A9D84 (notification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE completion (id INT AUTO_INCREMENT NOT NULL, lesson_id INT NOT NULL, user_id INT NOT NULL, completed TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_38D6377ECDF80196 (lesson_id), INDEX IDX_38D6377EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, status VARCHAR(20) DEFAULT NULL, item_order SMALLINT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_169E6FB9F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE course_user (course_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_45310B4F591CC992 (course_id), INDEX IDX_45310B4FA76ED395 (user_id), PRIMARY KEY(course_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE course_completion (id INT AUTO_INCREMENT NOT NULL, course_id INT DEFAULT NULL, user_id INT DEFAULT NULL, completed TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E73479FD591CC992 (course_id), INDEX IDX_E73479FDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lesson (id INT AUTO_INCREMENT NOT NULL, module_id INT NOT NULL, author_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, video_embeded LONGTEXT DEFAULT NULL, video_url VARCHAR(255) DEFAULT NULL, status VARCHAR(20) DEFAULT NULL, item_order SMALLINT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F87474F3AFC2B591 (module_id), INDEX IDX_F87474F3F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE module (id INT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, status VARCHAR(20) DEFAULT NULL, item_order SMALLINT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C242628F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE module_course (module_id INT NOT NULL, course_id INT NOT NULL, INDEX IDX_BC9D2F96AFC2B591 (module_id), INDEX IDX_BC9D2F96591CC992 (course_id), PRIMARY KEY(module_id, course_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE module_completion (id INT AUTO_INCREMENT NOT NULL, module_id INT NOT NULL, user_id INT DEFAULT NULL, completed TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_AD331CE3AFC2B591 (module_id), INDEX IDX_AD331CE3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, lesson_id INT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CFBDFA14A76ED395 (user_id), INDEX IDX_CFBDFA14CDF80196 (lesson_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, message LONGTEXT NOT NULL, title VARCHAR(255) DEFAULT NULL, sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', send_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_read TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(100) NOT NULL, content LONGTEXT NOT NULL, slug VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, fullname VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_connection_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', username VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, birth_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', address VARCHAR(100) DEFAULT NULL, score SMALLINT DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE badge ADD CONSTRAINT FK_FEF0481DC3C8852F FOREIGN KEY (badge_type_id) REFERENCES badge_type (id)');
        $this->addSql('ALTER TABLE badge_user ADD CONSTRAINT FK_299D3A50F7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE badge_user ADD CONSTRAINT FK_299D3A50A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cohort_course ADD CONSTRAINT FK_A8FE089635983C93 FOREIGN KEY (cohort_id) REFERENCES cohort (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cohort_course ADD CONSTRAINT FK_A8FE0896591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C727ACA70 FOREIGN KEY (parent_id) REFERENCES comment (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CEF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id)');
        $this->addSql('ALTER TABLE completion ADD CONSTRAINT FK_38D6377ECDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id)');
        $this->addSql('ALTER TABLE completion ADD CONSTRAINT FK_38D6377EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB9F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE course_user ADD CONSTRAINT FK_45310B4F591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_user ADD CONSTRAINT FK_45310B4FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_completion ADD CONSTRAINT FK_E73479FD591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_completion ADD CONSTRAINT FK_E73479FDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE lesson ADD CONSTRAINT FK_F87474F3AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE lesson ADD CONSTRAINT FK_F87474F3F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C242628F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE module_course ADD CONSTRAINT FK_BC9D2F96AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_course ADD CONSTRAINT FK_BC9D2F96591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_completion ADD CONSTRAINT FK_AD331CE3AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE module_completion ADD CONSTRAINT FK_AD331CE3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14CDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE badge DROP FOREIGN KEY FK_FEF0481DC3C8852F');
        $this->addSql('ALTER TABLE badge_user DROP FOREIGN KEY FK_299D3A50F7A2C2FC');
        $this->addSql('ALTER TABLE badge_user DROP FOREIGN KEY FK_299D3A50A76ED395');
        $this->addSql('ALTER TABLE cohort_course DROP FOREIGN KEY FK_A8FE089635983C93');
        $this->addSql('ALTER TABLE cohort_course DROP FOREIGN KEY FK_A8FE0896591CC992');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C727ACA70');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CEF1A9D84');
        $this->addSql('ALTER TABLE completion DROP FOREIGN KEY FK_38D6377ECDF80196');
        $this->addSql('ALTER TABLE completion DROP FOREIGN KEY FK_38D6377EA76ED395');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB9F675F31B');
        $this->addSql('ALTER TABLE course_user DROP FOREIGN KEY FK_45310B4F591CC992');
        $this->addSql('ALTER TABLE course_user DROP FOREIGN KEY FK_45310B4FA76ED395');
        $this->addSql('ALTER TABLE course_completion DROP FOREIGN KEY FK_E73479FD591CC992');
        $this->addSql('ALTER TABLE course_completion DROP FOREIGN KEY FK_E73479FDA76ED395');
        $this->addSql('ALTER TABLE lesson DROP FOREIGN KEY FK_F87474F3AFC2B591');
        $this->addSql('ALTER TABLE lesson DROP FOREIGN KEY FK_F87474F3F675F31B');
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C242628F675F31B');
        $this->addSql('ALTER TABLE module_course DROP FOREIGN KEY FK_BC9D2F96AFC2B591');
        $this->addSql('ALTER TABLE module_course DROP FOREIGN KEY FK_BC9D2F96591CC992');
        $this->addSql('ALTER TABLE module_completion DROP FOREIGN KEY FK_AD331CE3AFC2B591');
        $this->addSql('ALTER TABLE module_completion DROP FOREIGN KEY FK_AD331CE3A76ED395');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14A76ED395');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14CDF80196');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE badge');
        $this->addSql('DROP TABLE badge_user');
        $this->addSql('DROP TABLE badge_type');
        $this->addSql('DROP TABLE cohort');
        $this->addSql('DROP TABLE cohort_course');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE completion');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE course_user');
        $this->addSql('DROP TABLE course_completion');
        $this->addSql('DROP TABLE lesson');
        $this->addSql('DROP TABLE module');
        $this->addSql('DROP TABLE module_course');
        $this->addSql('DROP TABLE module_completion');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
