<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013065044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE collections (id INT AUTO_INCREMENT NOT NULL, birthday_bouquets VARCHAR(255) NOT NULL, anniversary_bouquets VARCHAR(255) NOT NULL, present_bouquets VARCHAR(255) NOT NULL, love_bouquets VARCHAR(255) NOT NULL, funeral_bouquets VARCHAR(255) NOT NULL, wedding_bouquets VARCHAR(255) NOT NULL, special_occasion VARCHAR(255) NOT NULL, custom_arrangements VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE collections');
    }
}
