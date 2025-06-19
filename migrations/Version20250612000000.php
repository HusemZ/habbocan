<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250612000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates news table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE news (
                id INT AUTO_INCREMENT NOT NULL,
                author_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                description VARCHAR(1000) DEFAULT NULL,
                category VARCHAR(100) DEFAULT NULL,
                cover_image VARCHAR(255) DEFAULT NULL,
                badge_availability VARCHAR(20) NOT NULL,
                badge_codes VARCHAR(255) DEFAULT NULL,
                status VARCHAR(20) NOT NULL,
                comments_enabled TINYINT(1) DEFAULT 1 NOT NULL,
                is_pinned TINYINT(1) DEFAULT 0 NOT NULL,
                content LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_1DD39950F675F31B (author_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE news
            ADD CONSTRAINT FK_1DD39950F675F31B
            FOREIGN KEY (author_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE news DROP FOREIGN KEY FK_1DD39950F675F31B');
        $this->addSql('DROP TABLE news');
    }
}
