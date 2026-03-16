<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create dashboard schema: shopping_list, shopping_list_product, todo_item with INT AUTO_INCREMENT ids and audit columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS shopping_list_product');
        $this->addSql('DROP TABLE IF EXISTS shopping_list');
        $this->addSql('DROP TABLE IF EXISTS todo_item');
        $this->addSql('DROP TABLE IF EXISTS product');

        $this->addSql('
            CREATE TABLE shopping_list (
                id          INT          NOT NULL AUTO_INCREMENT,
                owner_id    INT          NOT NULL,
                name        VARCHAR(255) NOT NULL,
                created_at  DATETIME     NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at  DATETIME     NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                created_by  INT          DEFAULT NULL,
                updated_by  INT          DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX IDX_SHOPPING_LIST_OWNER (owner_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE shopping_list_product (
                id               INT          NOT NULL AUTO_INCREMENT,
                shopping_list_id INT          NOT NULL,
                name             VARCHAR(255) NOT NULL,
                qty              INT          NOT NULL DEFAULT 1,
                weight           VARCHAR(100) DEFAULT NULL,
                position         INT          NOT NULL DEFAULT 0,
                created_at       DATETIME     NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at       DATETIME     NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                created_by       INT          DEFAULT NULL,
                updated_by       INT          DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX IDX_SLP_LIST (shopping_list_id),
                CONSTRAINT FK_SLP_LIST FOREIGN KEY (shopping_list_id)
                    REFERENCES shopping_list (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            CREATE TABLE todo_item (
                id         INT          NOT NULL AUTO_INCREMENT,
                owner_id   INT          NOT NULL,
                text       VARCHAR(500) NOT NULL,
                done       TINYINT(1)   NOT NULL DEFAULT 0,
                created_at DATETIME     NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME     NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                created_by INT          DEFAULT NULL,
                updated_by INT          DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX IDX_TODO_OWNER (owner_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS shopping_list_product');
        $this->addSql('DROP TABLE IF EXISTS shopping_list');
        $this->addSql('DROP TABLE IF EXISTS todo_item');
    }
}
