<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250301132358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE model3_d (id INT AUTO_INCREMENT NOT NULL, event_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, file_url VARCHAR(255) NOT NULL, INDEX IDX_B1A414D671F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE model3_d ADD CONSTRAINT FK_B1A414D671F7E88B FOREIGN KEY (event_id) REFERENCES event (idevent)');
        $this->addSql('ALTER TABLE conversation_user DROP FOREIGN KEY FK_5AECB5559AC0396');
        $this->addSql('ALTER TABLE conversation_user DROP FOREIGN KEY FK_5AECB555A76ED395');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FCD53EDB6');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE conversation_user');
        $this->addSql('DROP TABLE message');
        $this->addSql('ALTER TABLE candidature ADD cv VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE candidature_offre DROP FOREIGN KEY FK_91FCEF3B8D0EB82');
        $this->addSql('ALTER TABLE candidature_offre DROP FOREIGN KEY FK_91FCEF3BB6121583');
        $this->addSql('DROP INDEX IDX_91FCEF3B8D0EB82 ON candidature_offre');
        $this->addSql('ALTER TABLE candidature_offre DROP candidat_id, DROP competences, DROP lettre_motivation, DROP date_candidature, DROP is_read, DROP cv_filename, DROP updated_at, CHANGE emploi_id emploi_id INT DEFAULT NULL, CHANGE statut statut VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE candidature_offre ADD CONSTRAINT FK_91FCEF3BB6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE conversation_user (conversation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5AECB5559AC0396 (conversation_id), INDEX IDX_5AECB555A76ED395 (user_id), PRIMARY KEY(conversation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, conversation_id INT NOT NULL, content LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, file_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_read TINYINT(1) NOT NULL, read_at DATETIME DEFAULT NULL, INDEX IDX_B6BD307F9AC0396 (conversation_id), INDEX IDX_B6BD307FF624B39D (sender_id), INDEX IDX_B6BD307FCD53EDB6 (receiver_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT FK_5AECB5559AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT FK_5AECB555A76ED395 FOREIGN KEY (user_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE model3_d DROP FOREIGN KEY FK_B1A414D671F7E88B');
        $this->addSql('DROP TABLE model3_d');
        $this->addSql('ALTER TABLE candidature DROP cv');
        $this->addSql('ALTER TABLE candidature_offre DROP FOREIGN KEY FK_91FCEF3BB6121583');
        $this->addSql('ALTER TABLE candidature_offre ADD candidat_id INT NOT NULL, ADD competences LONGTEXT NOT NULL, ADD lettre_motivation LONGTEXT NOT NULL, ADD date_candidature DATETIME NOT NULL, ADD is_read TINYINT(1) NOT NULL, ADD cv_filename VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, CHANGE emploi_id emploi_id INT NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE candidature_offre ADD CONSTRAINT FK_91FCEF3B8D0EB82 FOREIGN KEY (candidat_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE candidature_offre ADD CONSTRAINT FK_91FCEF3BB6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id)');
        $this->addSql('CREATE INDEX IDX_91FCEF3B8D0EB82 ON candidature_offre (candidat_id)');
    }
}
