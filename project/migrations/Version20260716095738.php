<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260716095738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE coupon (
          id INT AUTO_INCREMENT NOT NULL,
          code VARCHAR(50) NOT NULL,
          TYPE VARCHAR(20) NOT NULL,
          value NUMERIC(10, 2) NOT NULL,
          is_active TINYINT(1) DEFAULT 1 NOT NULL,
          valid_from DATE DEFAULT NULL,
          valid_until DATE DEFAULT NULL,
          max_uses INT DEFAULT NULL,
          max_uses_per_user INT DEFAULT 1,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          UNIQUE INDEX UNIQ_64BF3F0277153098 (code),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE coupon_apartment (
          coupon_id INT NOT NULL,
          apartment_id INT NOT NULL,
          INDEX IDX_85B0BA8A66C5951B (coupon_id),
          INDEX IDX_85B0BA8A176DFE85 (apartment_id),
          PRIMARY KEY(coupon_id, apartment_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE coupon_redemption (
          id INT AUTO_INCREMENT NOT NULL,
          coupon_id INT NOT NULL,
          user_id INT NOT NULL,
          reservation_id INT NOT NULL,
          discount_amount DOUBLE PRECISION DEFAULT NULL,
          created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
          INDEX IDX_4DC2052266C5951B (coupon_id),
          INDEX IDX_4DC20522A76ED395 (user_id),
          INDEX IDX_4DC20522B83297E7 (reservation_id),
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE
          coupon_apartment
        ADD
          CONSTRAINT FK_85B0BA8A66C5951B FOREIGN KEY (coupon_id) REFERENCES coupon (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          coupon_apartment
        ADD
          CONSTRAINT FK_85B0BA8A176DFE85 FOREIGN KEY (apartment_id) REFERENCES apartment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE
          coupon_redemption
        ADD
          CONSTRAINT FK_4DC2052266C5951B FOREIGN KEY (coupon_id) REFERENCES coupon (id)');
        $this->addSql('ALTER TABLE
          coupon_redemption
        ADD
          CONSTRAINT FK_4DC20522A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE
          coupon_redemption
        ADD
          CONSTRAINT FK_4DC20522B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE
          reservation
        ADD
          coupon_code VARCHAR(50) DEFAULT NULL,
        ADD
          discount_amount DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coupon_apartment DROP FOREIGN KEY FK_85B0BA8A66C5951B');
        $this->addSql('ALTER TABLE coupon_apartment DROP FOREIGN KEY FK_85B0BA8A176DFE85');
        $this->addSql('ALTER TABLE coupon_redemption DROP FOREIGN KEY FK_4DC2052266C5951B');
        $this->addSql('ALTER TABLE coupon_redemption DROP FOREIGN KEY FK_4DC20522A76ED395');
        $this->addSql('ALTER TABLE coupon_redemption DROP FOREIGN KEY FK_4DC20522B83297E7');
        $this->addSql('DROP TABLE coupon');
        $this->addSql('DROP TABLE coupon_apartment');
        $this->addSql('DROP TABLE coupon_redemption');
        $this->addSql('ALTER TABLE reservation DROP coupon_code, DROP discount_amount');
    }
}
