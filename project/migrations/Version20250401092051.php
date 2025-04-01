<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250401092051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE apartment_equipment (apartment_id INT NOT NULL, equipment_id INT NOT NULL, INDEX IDX_55E6E60B176DFE85 (apartment_id), INDEX IDX_55E6E60B517FE9FE (equipment_id), PRIMARY KEY(apartment_id, equipment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE apartment_equipment ADD CONSTRAINT FK_55E6E60B176DFE85 FOREIGN KEY (apartment_id) REFERENCES apartment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE apartment_equipment ADD CONSTRAINT FK_55E6E60B517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE apartment_equipment DROP FOREIGN KEY FK_55E6E60B176DFE85');
        $this->addSql('ALTER TABLE apartment_equipment DROP FOREIGN KEY FK_55E6E60B517FE9FE');
        $this->addSql('DROP TABLE apartment_equipment');
    }
}
