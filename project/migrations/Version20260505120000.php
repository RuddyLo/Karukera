<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create social_media table for footer social links management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE social_media (id INT AUTO_INCREMENT NOT NULL, platform VARCHAR(50) NOT NULL, url VARCHAR(255) NOT NULL, icon VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_6AA73B6E2A93DBE6 (platform), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE social_media');
    }
}
