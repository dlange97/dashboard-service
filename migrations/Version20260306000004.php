<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shopping list status and shopping product bought flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE shopping_list ADD status VARCHAR(20) NOT NULL DEFAULT 'active'");
        $this->addSql("UPDATE shopping_list SET status = 'active' WHERE status IS NULL OR status = ''");

        $this->addSql('ALTER TABLE shopping_list_product ADD bought TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('UPDATE shopping_list_product SET bought = 0 WHERE bought IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shopping_list DROP status');
        $this->addSql('ALTER TABLE shopping_list_product DROP bought');
    }
}
