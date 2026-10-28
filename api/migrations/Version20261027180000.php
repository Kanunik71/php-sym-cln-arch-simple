<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261027180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tasks.owner_id';
    }

    public function preUp(Schema $schema): void
    {
        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tasks');

        if ($count > 0) {
            throw new RuntimeException('Cannot add tasks.owner_id: table tasks is not empty and has no creator to backfill. Recreate the database.');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tasks ADD owner_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_505865977E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_505865977E3C61F9 ON tasks (owner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_505865977E3C61F9');
        $this->addSql('DROP INDEX IDX_505865977E3C61F9 ON tasks');
        $this->addSql('ALTER TABLE tasks DROP owner_id');
    }
}
