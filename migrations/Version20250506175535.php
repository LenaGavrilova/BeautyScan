<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506175535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ingredient_synonyms DROP CONSTRAINT fk_c872489933fe08c');
        $this->addSql('DROP INDEX idx_fc11e459933fe08c');
        $this->addSql('DROP INDEX uniq_fc11e4595e237e06');
        $this->addSql('ALTER TABLE ingredient_synonyms ADD ingredient_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ingredient_synonyms DROP ingredient_id');
        $this->addSql('ALTER TABLE ingredient_synonyms RENAME COLUMN name TO synonym_name');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ingredient_synonyms ADD ingredient_id INT NOT NULL');
        $this->addSql('ALTER TABLE ingredient_synonyms ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ingredient_synonyms DROP synonym_name');
        $this->addSql('ALTER TABLE ingredient_synonyms DROP ingredient_name');
        $this->addSql('ALTER TABLE ingredient_synonyms ADD CONSTRAINT fk_c872489933fe08c FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fc11e459933fe08c ON ingredient_synonyms (ingredient_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_fc11e4595e237e06 ON ingredient_synonyms (name)');
    }
}
