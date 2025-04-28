<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428173224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_4b60114f142a9a93');
        $this->addSql('DROP INDEX uniq_4b60114fa2a71819');
        $this->addSql('DROP INDEX uniq_4b60114f9134d4bf');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE UNIQUE INDEX uniq_4b60114f142a9a93 ON ingredients (inciname)');
        $this->addSql('CREATE UNIQUE INDEX uniq_4b60114fa2a71819 ON ingredients (latin_name)');
        $this->addSql('CREATE UNIQUE INDEX uniq_4b60114f9134d4bf ON ingredients (traditional_name)');
    }
}
