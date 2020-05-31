<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200531004608 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, phone_number_patient VARCHAR(255) NOT NULL, email_patient VARCHAR(255) NOT NULL, schedule_patient LONGTEXT NOT NULL, state VARCHAR(255) NOT NULL, schedule_by_doctor DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fos_user ADD appointment_id INT DEFAULT NULL, ADD appointment_as_patient_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fos_user ADD CONSTRAINT FK_957A6479E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
        $this->addSql('ALTER TABLE fos_user ADD CONSTRAINT FK_957A647992C87B1A FOREIGN KEY (appointment_as_patient_id) REFERENCES appointment (id)');
        $this->addSql('CREATE INDEX IDX_957A6479E5B533F9 ON fos_user (appointment_id)');
        $this->addSql('CREATE INDEX IDX_957A647992C87B1A ON fos_user (appointment_as_patient_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_user DROP FOREIGN KEY FK_957A6479E5B533F9');
        $this->addSql('ALTER TABLE fos_user DROP FOREIGN KEY FK_957A647992C87B1A');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP INDEX IDX_957A6479E5B533F9 ON fos_user');
        $this->addSql('DROP INDEX IDX_957A647992C87B1A ON fos_user');
        $this->addSql('ALTER TABLE fos_user DROP appointment_id, DROP appointment_as_patient_id');
    }
}
