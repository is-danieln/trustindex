<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815094407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extract companies from reviews and replace duplicated names with a relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE company (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, normalized_name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_company_normalized_name ON company (normalized_name)');
        $this->addSql('INSERT INTO company (name, normalized_name) SELECT review.company_name, review.company_name_key FROM review WHERE review.id = (SELECT MIN(first_review.id) FROM review first_review WHERE first_review.company_name_key = review.company_name_key)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT review.id, review.rating, review.review_text, review.author_email, review.created_at, review.updated_at, company.id AS company_id FROM review INNER JOIN company ON company.normalized_name = review.company_name_key');
        $this->addSql('DROP TABLE review');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating INTEGER NOT NULL, review_text CLOB NOT NULL, author_email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, company_id INTEGER NOT NULL, CONSTRAINT FK_794381C6979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO review (id, rating, review_text, author_email, created_at, updated_at, company_id) SELECT id, rating, review_text, author_email, created_at, updated_at, company_id FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
        $this->addSql('CREATE INDEX IDX_794381C6979B1AD6 ON review (company_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__review AS SELECT review.id, review.rating, review.review_text, review.author_email, review.created_at, review.updated_at, company.name AS company_name, company.normalized_name AS company_name_key FROM review INNER JOIN company ON company.id = review.company_id');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE company');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating INTEGER NOT NULL, review_text CLOB NOT NULL, author_email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, company_name VARCHAR(255) NOT NULL, company_name_key VARCHAR(255) DEFAULT \'\' NOT NULL)');
        $this->addSql('INSERT INTO review (id, rating, review_text, author_email, created_at, updated_at, company_name, company_name_key) SELECT id, rating, review_text, author_email, created_at, updated_at, company_name, company_name_key FROM __temp__review');
        $this->addSql('DROP TABLE __temp__review');
        $this->addSql('CREATE INDEX idx_review_company_name_key ON review (company_name_key)');
    }
}
