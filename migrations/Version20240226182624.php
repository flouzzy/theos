<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240226182624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course ADD item_order SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE lesson ADD item_order SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE module ADD item_order SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE module DROP item_order');
        $this->addSql('ALTER TABLE lesson DROP item_order');
        $this->addSql('ALTER TABLE course DROP item_order');
    }
}
