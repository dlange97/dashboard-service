<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260602000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category column to shopping_list_product';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shopping_list_product ADD category VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shopping_list_product DROP category');
    }
}
