<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509120232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ingredient_synonyms ALTER synonym_name TYPE JSON USING synonym_name::json');
        $this->addSql('ALTER TABLE ingredient_synonyms ALTER synonym_name DROP NOT NULL');
        $this->addSql('ALTER TABLE ingredient_synonyms ALTER ingredient_name TYPE TEXT');
        $this->addSql('COMMENT ON COLUMN ingredient_synonyms.ingredient_name IS \'(DC2Type:array)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE ingredient_synonyms ALTER synonym_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE ingredient_synonyms ALTER synonym_name SET NOT NULL');
        $this->addSql('ALTER TABLE ingredient_synonyms ALTER ingredient_name TYPE VARCHAR(255)');
        $this->addSql('COMMENT ON COLUMN ingredient_synonyms.ingredient_name IS NULL');
    }
}
