<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428172707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ingredients ALTER traditional_name TYPE TEXT');
        $this->addSql('ALTER TABLE ingredients ALTER latin_name TYPE TEXT');
        $this->addSql('ALTER TABLE ingredients ALTER inciname TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ingredients ALTER traditional_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ingredients ALTER latin_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ingredients ALTER inciname TYPE VARCHAR(255)');
    }
}
