<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250628100224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING created_at::timestamp(0) without time zone');
        $this->addSql('ALTER TABLE "user" ALTER last_login DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER last_login TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING last_login::timestamp(0) without time zone');
        $this->addSql('ALTER TABLE "user" ALTER last_login SET DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER created_at TYPE VARCHAR(255)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER last_login TYPE VARCHAR(255)
        SQL);
    }
}
