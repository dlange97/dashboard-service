<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shared user IDs for todo items and shopping lists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE todo_item ADD shared_with_user_ids JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("UPDATE todo_item SET shared_with_user_ids = '[]'");

        $this->addSql("ALTER TABLE shopping_list ADD shared_with_user_ids JSON NOT NULL COMMENT '(DC2Type:json)'");
        $this->addSql("UPDATE shopping_list SET shared_with_user_ids = '[]'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE todo_item DROP shared_with_user_ids');
        $this->addSql('ALTER TABLE shopping_list DROP shared_with_user_ids');
    }
}
