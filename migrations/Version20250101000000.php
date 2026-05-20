<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000000 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Rename rocher_coins to coins in user table';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE rocher_coins coins INT DEFAULT 0 NOT NULL');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE coins rocher_coins INT DEFAULT 0 NOT NULL');
    }
}