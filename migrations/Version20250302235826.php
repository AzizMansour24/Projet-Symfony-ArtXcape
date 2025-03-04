<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250302235826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD reset_token VARCHAR(255) DEFAULT NULL, ADD last_login_at DATETIME DEFAULT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE numtlf numtlf VARCHAR(255) DEFAULT NULL, CHANGE photo_de_profile avatar_url VARCHAR(255) DEFAULT NULL, CHANGE updated_at reset_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` ADD photo_de_profile VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, DROP avatar_url, DROP reset_token, DROP reset_token_expires_at, DROP last_login_at, CHANGE email email VARCHAR(255) NOT NULL, CHANGE numtlf numtlf VARCHAR(20) DEFAULT NULL');
    }
}
