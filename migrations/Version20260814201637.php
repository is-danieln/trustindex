<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814201637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a normalized and indexed company key to reviews';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE review ADD COLUMN company_name_key VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('UPDATE review SET company_name_key = LOWER(TRIM(company_name))');
        $this->addSql('CREATE INDEX idx_review_company_name_key ON review (company_name_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT id, company_name, rating, review_text, author_email, created_at, updated_at FROM review');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, company_name VARCHAR(255) NOT NULL, rating INTEGER NOT NULL, review_text CLOB NOT NULL, author_email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO review (id, company_name, rating, review_text, author_email, created_at, updated_at) SELECT id, company_name, rating, review_text, author_email, created_at, updated_at FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
    }
}
