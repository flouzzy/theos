<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create EventCategory entity and update Event relation with data migration';
    }

    public function up(Schema $schema): void
    {
        // Create the new table
        $this->addSql('CREATE TABLE event_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add the relation column
        $this->addSql('ALTER TABLE event ADD type_id INT DEFAULT NULL');

        // Migrate existing string types to the new table
        // First, ensure all distinct string types currently in `event` are inserted into `event_category`
        $this->addSql('INSERT INTO event_category (name) SELECT DISTINCT type FROM event WHERE type IS NOT NULL');

        // Update the existing events to link to their new category record
        $this->addSql('UPDATE event e INNER JOIN event_category ec ON e.type = ec.name SET e.type_id = ec.id');

        // Drop the old string column
        $this->addSql('ALTER TABLE event DROP type');

        // Add foreign key and index
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_EVENT_TYPE FOREIGN KEY (type_id) REFERENCES event_category (id)');
        $this->addSql('CREATE INDEX IDX_EVENT_TYPE ON event (type_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key and index
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_EVENT_TYPE');
        $this->addSql('DROP INDEX IDX_EVENT_TYPE ON event');

        // Re-add the string column
        $this->addSql('ALTER TABLE event ADD type VARCHAR(50) DEFAULT \'webinar\' NOT NULL');

        // Reverse data migration
        $this->addSql('UPDATE event e INNER JOIN event_category ec ON e.type_id = ec.id SET e.type = ec.name');

        // Drop relation column and the new table
        $this->addSql('ALTER TABLE event DROP type_id');
        $this->addSql('DROP TABLE event_category');
    }
}
