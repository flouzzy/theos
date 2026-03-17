<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317201512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD google_id VARCHAR(255) DEFAULT NULL, ADD linkedin_id VARCHAR(255) DEFAULT NULL, ADD website_url VARCHAR(255) DEFAULT NULL, ADD github_url VARCHAR(255) DEFAULT NULL, ADD is_profile_public TINYINT DEFAULT 0 NOT NULL, ADD confetti_color VARCHAR(7) DEFAULT \'#000000\' NOT NULL, ADD login_token VARCHAR(128) DEFAULT NULL, ADD login_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649594766AF ON user (login_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649594766AF ON `user`');
        $this->addSql('ALTER TABLE `user` DROP google_id, DROP linkedin_id, DROP website_url, DROP github_url, DROP is_profile_public, DROP confetti_color, DROP login_token, DROP login_token_expires_at');
    }
}
