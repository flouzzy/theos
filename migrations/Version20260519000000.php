<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add message_key, title_key and translation_params to notification for display-time translation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification ADD message_key LONGTEXT DEFAULT NULL, ADD title_key VARCHAR(255) DEFAULT NULL, ADD translation_params JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP message_key, DROP title_key, DROP translation_params');
    }
}
