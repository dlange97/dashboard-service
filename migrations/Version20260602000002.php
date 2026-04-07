<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260602000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix note audit column types to INT to match TimestampableTrait';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note MODIFY created_by INT DEFAULT NULL, MODIFY updated_by INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note MODIFY created_by VARCHAR(36) DEFAULT NULL, MODIFY updated_by VARCHAR(36) DEFAULT NULL');
    }
}