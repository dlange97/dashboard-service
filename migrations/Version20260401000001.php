<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create note table with instanceId, audit columns, and sharing support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE note (
                id               INT          NOT NULL AUTO_INCREMENT,
                owner_id         VARCHAR(36)  NOT NULL,
                title            VARCHAR(500) NOT NULL,
                content          LONGTEXT     NOT NULL,
                shared_with_user_ids JSON     NOT NULL,
                instance_id      VARCHAR(36)  DEFAULT NULL,
                created_at       DATETIME     NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at       DATETIME     NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                created_by       VARCHAR(36)  DEFAULT NULL,
                updated_by       VARCHAR(36)  DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX IDX_NOTE_OWNER (owner_id),
                INDEX IDX_NOTE_INSTANCE (instance_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS note');
    }
}
