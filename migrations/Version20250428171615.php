<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428171615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX uniq_c8724895e237e06 RENAME TO UNIQ_FC11E4595E237E06');
        $this->addSql('ALTER INDEX idx_c872489933fe08c RENAME TO IDX_FC11E459933FE08C');
        $this->addSql('DROP INDEX uniq_4b60114f5e237e06');
        $this->addSql('ALTER TABLE ingredients ADD latin_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ingredients ADD inciname VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ingredients ADD naturalness VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE ingredients ADD safety VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ingredients RENAME COLUMN name TO traditional_name');
        $this->addSql('ALTER TABLE ingredients RENAME COLUMN safety_level TO danger_factor');
        $this->addSql('ALTER TABLE ingredients RENAME COLUMN description TO usages');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B60114F9134D4BF ON ingredients (traditional_name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B60114FA2A71819 ON ingredients (latin_name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B60114F142A9A93 ON ingredients (inciname)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B60114FAAFD544F ON ingredients (safety)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER INDEX idx_fc11e459933fe08c RENAME TO idx_c872489933fe08c');
        $this->addSql('ALTER INDEX uniq_fc11e4595e237e06 RENAME TO uniq_c8724895e237e06');
        $this->addSql('DROP INDEX UNIQ_4B60114F9134D4BF');
        $this->addSql('DROP INDEX UNIQ_4B60114FA2A71819');
        $this->addSql('DROP INDEX UNIQ_4B60114F142A9A93');
        $this->addSql('DROP INDEX UNIQ_4B60114FAAFD544F');
        $this->addSql('ALTER TABLE ingredients ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ingredients ADD safety_level VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE ingredients DROP traditional_name');
        $this->addSql('ALTER TABLE ingredients DROP latin_name');
        $this->addSql('ALTER TABLE ingredients DROP inciname');
        $this->addSql('ALTER TABLE ingredients DROP danger_factor');
        $this->addSql('ALTER TABLE ingredients DROP naturalness');
        $this->addSql('ALTER TABLE ingredients DROP safety');
        $this->addSql('ALTER TABLE ingredients RENAME COLUMN usages TO description');
        $this->addSql('CREATE UNIQUE INDEX uniq_4b60114f5e237e06 ON ingredients (name)');
    }
}
