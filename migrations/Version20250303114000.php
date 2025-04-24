<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250303114000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создание таблиц для ингредиентов и их синонимов';
    }

    public function up(Schema $schema): void
    {
        // Создаем таблицу ингредиентов
        $this->addSql('CREATE TABLE ingredients (
            id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            safety_level VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B60114F5E237E06 ON ingredients (name)');
        $this->addSql('CREATE SEQUENCE ingredients_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE ingredients ALTER id SET DEFAULT nextval(\'ingredients_id_seq\')');

        // Создаем таблицу синонимов ингредиентов
        $this->addSql('CREATE TABLE ingredient_synonyms (
            id INT NOT NULL,
            ingredient_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C8724895E237E06 ON ingredient_synonyms (name)');
        $this->addSql('CREATE INDEX IDX_C872489933FE08C ON ingredient_synonyms (ingredient_id)');
        $this->addSql('CREATE SEQUENCE ingredient_synonyms_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE ingredient_synonyms ALTER id SET DEFAULT nextval(\'ingredient_synonyms_id_seq\')');
        
        // Добавляем внешний ключ для связи синонимов с ингредиентами
        $this->addSql('ALTER TABLE ingredient_synonyms ADD CONSTRAINT FK_C872489933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // Удаляем таблицы в обратном порядке
        $this->addSql('ALTER TABLE ingredient_synonyms DROP CONSTRAINT FK_C872489933FE08C');
        $this->addSql('DROP TABLE ingredient_synonyms');
        $this->addSql('DROP SEQUENCE ingredient_synonyms_id_seq CASCADE');
        $this->addSql('DROP TABLE ingredients');
        $this->addSql('DROP SEQUENCE ingredients_id_seq CASCADE');
    }
} 