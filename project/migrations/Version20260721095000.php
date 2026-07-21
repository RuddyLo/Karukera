<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721095000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la date de naissance et du pays de résidence sur User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD birth_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD country VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP birth_date, DROP country');
    }
}
