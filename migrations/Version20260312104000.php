<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312104000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification preferences to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD email_notifications TINYINT(1) DEFAULT 1 NOT NULL, ADD push_notifications TINYINT(1) DEFAULT 1 NOT NULL, ADD lesson_reminders TINYINT(1) DEFAULT 1 NOT NULL, ADD weekly_summary TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP email_notifications, DROP push_notifications, DROP lesson_reminders, DROP weekly_summary');
    }
}
