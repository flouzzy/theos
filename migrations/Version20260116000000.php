<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260116000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gamification fields to User and Badge entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE badge ADD title VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE badge ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE badge ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE badge ALTER COLUMN title DROP DEFAULT');

        $this->addSql('ALTER TABLE "user" ADD xp INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD streak INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD last_streak_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".last_streak_date IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE badge DROP title');
        $this->addSql('ALTER TABLE badge DROP description');
        $this->addSql('ALTER TABLE badge DROP image');

        $this->addSql('ALTER TABLE "user" DROP xp');
        $this->addSql('ALTER TABLE "user" DROP streak');
        $this->addSql('ALTER TABLE "user" DROP last_streak_date');
    }
}
