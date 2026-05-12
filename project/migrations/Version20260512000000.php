<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add minimum_stay_period table for apartment booking constraints';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE minimum_stay_period (id INT AUTO_INCREMENT NOT NULL, apartment_id INT NOT NULL, minimum_days INT NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, INDEX IDX_MINIMUM_STAY_APARTMENT (apartment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE minimum_stay_period ADD CONSTRAINT FK_MINIMUM_STAY_APARTMENT FOREIGN KEY (apartment_id) REFERENCES apartment (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE minimum_stay_period DROP FOREIGN KEY FK_MINIMUM_STAY_APARTMENT');
        $this->addSql('DROP TABLE minimum_stay_period');
    }
}
