<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add due date for todo items and shopping lists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE todo_item ADD due_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE shopping_list ADD due_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE todo_item DROP due_date');
        $this->addSql('ALTER TABLE shopping_list DROP due_date');
    }
}
