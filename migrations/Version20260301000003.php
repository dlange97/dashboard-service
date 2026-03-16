<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migrate ownerId columns from INT to VARCHAR(36) to support UUID strings
 * issued by the auth-service.
 */
final class Version20260301000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change shopping_list.owner_id and todo_item.owner_id from INT to VARCHAR(36) for UUID support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shopping_list MODIFY owner_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE todo_item MODIFY owner_id VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shopping_list MODIFY owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE todo_item MODIFY owner_id INT DEFAULT NULL');
    }
}
