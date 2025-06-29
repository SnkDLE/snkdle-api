<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250628123213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE character (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, species TEXT NOT NULL, gender VARCHAR(255) NOT NULL, age INT NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN character.species IS '(DC2Type:array)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE question (id SERIAL NOT NULL, type VARCHAR(255) NOT NULL, external_character_id INT NOT NULL, correct_answer VARCHAR(255) NOT NULL, prompt_data VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE quiz (id SERIAL NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE quiz_question (quiz_id INT NOT NULL, question_id INT NOT NULL, PRIMARY KEY(quiz_id, question_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6033B00B853CD175 ON quiz_question (quiz_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6033B00B1E27F6BF ON quiz_question (question_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_question ADD CONSTRAINT FK_6033B00B853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_question ADD CONSTRAINT FK_6033B00B1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD api_token VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_question DROP CONSTRAINT FK_6033B00B853CD175
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_question DROP CONSTRAINT FK_6033B00B1E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE character
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE question
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE quiz
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE quiz_question
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP api_token
        SQL);
    }
}
