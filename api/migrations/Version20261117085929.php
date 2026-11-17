<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261117085929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE asset_file (
              position INT NOT NULL,
              asset_id UUID NOT NULL,
              file_id UUID NOT NULL,
              PRIMARY KEY (asset_id, file_id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_68FBF3D85DA1941 ON asset_file (asset_id)');
        $this->addSql('CREATE INDEX IDX_68FBF3D893CB796C ON asset_file (file_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE assets (
              id UUID NOT NULL,
              name VARCHAR(255) NOT NULL,
              price NUMERIC(10, 2) NOT NULL,
              type VARCHAR(255) NOT NULL,
              owner_id UUID NOT NULL,
              created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_79D17D8E7E3C61F9 ON assets (owner_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE asset_task (
              asset_id UUID NOT NULL,
              task_id UUID NOT NULL,
              PRIMARY KEY (asset_id, task_id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_B61A1EED5DA1941 ON asset_task (asset_id)');
        $this->addSql('CREATE INDEX IDX_B61A1EED8DB60186 ON asset_task (task_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE files (
              id UUID NOT NULL,
              owner_id UUID NOT NULL,
              original_name VARCHAR(255) NOT NULL,
              mime_type VARCHAR(127) NOT NULL,
              size_bytes INT NOT NULL,
              storage_key VARCHAR(512) NOT NULL,
              status VARCHAR(255) NOT NULL,
              created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_63540597E3C61F9 ON files (owner_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_tokens (
              id UUID NOT NULL,
              user_id UUID NOT NULL,
              token_hash VARCHAR(64) NOT NULL,
              family_id UUID NOT NULL,
              created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_REFRESH_TOKEN_USER ON refresh_tokens (user_id)');
        $this->addSql('CREATE INDEX IDX_REFRESH_TOKEN_FAMILY ON refresh_tokens (family_id)');
        $this->addSql('CREATE INDEX IDX_REFRESH_TOKEN_EXPIRES ON refresh_tokens (expires_at)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_REFRESH_TOKEN_HASH ON refresh_tokens (token_hash)');
        $this->addSql(<<<'SQL'
            CREATE TABLE task_groups (
              id UUID NOT NULL,
              owner_id UUID NOT NULL,
              name VARCHAR(255) NOT NULL,
              status VARCHAR(255) NOT NULL,
              created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_CBC4BDA37E3C61F9 ON task_groups (owner_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE task_group_task (
              task_group_id UUID NOT NULL,
              task_id UUID NOT NULL,
              PRIMARY KEY (task_group_id, task_id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_6737CBFABE94330B ON task_group_task (task_group_id)');
        $this->addSql('CREATE INDEX IDX_6737CBFA8DB60186 ON task_group_task (task_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE task_users (
              id UUID NOT NULL,
              user_id UUID NOT NULL,
              status VARCHAR(255) NOT NULL,
              task_id UUID NOT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TASK_USER ON task_users (task_id, user_id)');
        $this->addSql('CREATE INDEX IDX_D327BEC98DB60186 ON task_users (task_id)');
        $this->addSql('CREATE INDEX IDX_D327BEC9A76ED395 ON task_users (user_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE tasks (
              id UUID NOT NULL,
              owner_id UUID NOT NULL,
              name VARCHAR(255) NOT NULL,
              parent_id UUID DEFAULT NULL,
              estimate_time INT DEFAULT NULL,
              status VARCHAR(255) NOT NULL,
              created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              cancel_reason VARCHAR(1000) DEFAULT NULL,
              finished_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
              cancellation_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_505865977E3C61F9 ON tasks (owner_id)');
        $this->addSql('CREATE INDEX IDX_50586597727ACA70 ON tasks (parent_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE user_notification_preferences (
              id UUID NOT NULL,
              user_id UUID NOT NULL,
              type VARCHAR(50) NOT NULL,
              enabled BOOLEAN NOT NULL,
              email_enabled BOOLEAN NOT NULL,
              sms_enabled BOOLEAN NOT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_USER_NOTIFICATION_TYPE ON user_notification_preferences (user_id, type)
        SQL);
        $this->addSql('CREATE INDEX IDX_207F257FA76ED395 ON user_notification_preferences (user_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
              id UUID NOT NULL,
              fname VARCHAR(100) DEFAULT NULL,
              lname VARCHAR(100) DEFAULT NULL,
              email VARCHAR(255) NOT NULL,
              password VARCHAR(255) NOT NULL,
              city VARCHAR(100) DEFAULT NULL,
              phone VARCHAR(20) DEFAULT NULL,
              created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              avatar_file_id UUID DEFAULT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E945A576B2 ON users (avatar_file_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              asset_file
            ADD
              CONSTRAINT FK_68FBF3D85DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              asset_file
            ADD
              CONSTRAINT FK_68FBF3D893CB796C FOREIGN KEY (file_id) REFERENCES files (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              assets
            ADD
              CONSTRAINT FK_79D17D8E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              asset_task
            ADD
              CONSTRAINT FK_B61A1EED5DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id) ON DELETE CASCADE NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              asset_task
            ADD
              CONSTRAINT FK_B61A1EED8DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              files
            ADD
              CONSTRAINT FK_63540597E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              refresh_tokens
            ADD
              CONSTRAINT FK_9BACE7E1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              task_groups
            ADD
              CONSTRAINT FK_CBC4BDA37E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              task_group_task
            ADD
              CONSTRAINT FK_6737CBFABE94330B FOREIGN KEY (task_group_id) REFERENCES task_groups (id) ON DELETE CASCADE NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              task_group_task
            ADD
              CONSTRAINT FK_6737CBFA8DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              task_users
            ADD
              CONSTRAINT FK_D327BEC98DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              task_users
            ADD
              CONSTRAINT FK_D327BEC9A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              tasks
            ADD
              CONSTRAINT FK_505865977E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              tasks
            ADD
              CONSTRAINT FK_50586597727ACA70 FOREIGN KEY (parent_id) REFERENCES tasks (id) ON DELETE
            SET
              NULL NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user_notification_preferences
            ADD
              CONSTRAINT FK_207F257FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              users
            ADD
              CONSTRAINT FK_1483A5E945A576B2 FOREIGN KEY (avatar_file_id) REFERENCES files (id) ON DELETE
            SET
              NULL NOT DEFERRABLE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE asset_file DROP CONSTRAINT FK_68FBF3D85DA1941');
        $this->addSql('ALTER TABLE asset_file DROP CONSTRAINT FK_68FBF3D893CB796C');
        $this->addSql('ALTER TABLE assets DROP CONSTRAINT FK_79D17D8E7E3C61F9');
        $this->addSql('ALTER TABLE asset_task DROP CONSTRAINT FK_B61A1EED5DA1941');
        $this->addSql('ALTER TABLE asset_task DROP CONSTRAINT FK_B61A1EED8DB60186');
        $this->addSql('ALTER TABLE files DROP CONSTRAINT FK_63540597E3C61F9');
        $this->addSql('ALTER TABLE refresh_tokens DROP CONSTRAINT FK_9BACE7E1A76ED395');
        $this->addSql('ALTER TABLE task_groups DROP CONSTRAINT FK_CBC4BDA37E3C61F9');
        $this->addSql('ALTER TABLE task_group_task DROP CONSTRAINT FK_6737CBFABE94330B');
        $this->addSql('ALTER TABLE task_group_task DROP CONSTRAINT FK_6737CBFA8DB60186');
        $this->addSql('ALTER TABLE task_users DROP CONSTRAINT FK_D327BEC98DB60186');
        $this->addSql('ALTER TABLE task_users DROP CONSTRAINT FK_D327BEC9A76ED395');
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_505865977E3C61F9');
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_50586597727ACA70');
        $this->addSql('ALTER TABLE user_notification_preferences DROP CONSTRAINT FK_207F257FA76ED395');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E945A576B2');
        $this->addSql('DROP TABLE asset_file');
        $this->addSql('DROP TABLE assets');
        $this->addSql('DROP TABLE asset_task');
        $this->addSql('DROP TABLE files');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE task_groups');
        $this->addSql('DROP TABLE task_group_task');
        $this->addSql('DROP TABLE task_users');
        $this->addSql('DROP TABLE tasks');
        $this->addSql('DROP TABLE user_notification_preferences');
        $this->addSql('DROP TABLE users');
    }
}
