<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add color column to note table for UI highlighting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE note ADD color VARCHAR(7) DEFAULT '#fef3c7' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note DROP color');
    }
}
