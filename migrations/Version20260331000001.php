<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add instance_id column to todo_item, shopping_list and shopping_list_product tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE todo_item ADD instance_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE shopping_list ADD instance_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE shopping_list_product ADD instance_id VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shopping_list_product DROP instance_id');
        $this->addSql('ALTER TABLE shopping_list DROP instance_id');
        $this->addSql('ALTER TABLE todo_item DROP instance_id');
    }
}
