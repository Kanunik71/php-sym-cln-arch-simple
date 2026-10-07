<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialize database schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE asset_file (position INT NOT NULL, asset_id BINARY(16) NOT NULL, file_id BINARY(16) NOT NULL, INDEX IDX_68FBF3D85DA1941 (asset_id), INDEX IDX_68FBF3D893CB796C (file_id), PRIMARY KEY (asset_id, file_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE assets (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, price NUMERIC(10, 2) NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, owner_id BINARY(16) NOT NULL, INDEX IDX_79D17D8E7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE asset_task (asset_id BINARY(16) NOT NULL, task_id BINARY(16) NOT NULL, INDEX IDX_B61A1EED5DA1941 (asset_id), INDEX IDX_B61A1EED8DB60186 (task_id), PRIMARY KEY (asset_id, task_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE files (id BINARY(16) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(127) NOT NULL, size_bytes INT NOT NULL, storage_key VARCHAR(512) NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, owner_id BINARY(16) NOT NULL, INDEX IDX_63540597E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_groups (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, owner_id BINARY(16) NOT NULL, INDEX IDX_95FAE6E07E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_group_task (task_group_id BINARY(16) NOT NULL, task_id BINARY(16) NOT NULL, INDEX IDX_6737CBFABE94330B (task_group_id), INDEX IDX_6737CBFA8DB60186 (task_id), PRIMARY KEY (task_group_id, task_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_users (id BINARY(16) NOT NULL, status VARCHAR(255) NOT NULL, task_id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_D327BEC98DB60186 (task_id), INDEX IDX_D327BEC9A76ED395 (user_id), UNIQUE INDEX UNIQ_TASK_USER (task_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tasks (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, estimate_time INT DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, cancel_reason VARCHAR(1000) DEFAULT NULL, finished_date DATETIME DEFAULT NULL, cancellation_date DATETIME DEFAULT NULL, parent_id BINARY(16) DEFAULT NULL, INDEX IDX_50586597727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_notification_preferences (id BINARY(16) NOT NULL, type VARCHAR(50) NOT NULL, enabled TINYINT NOT NULL, email_enabled TINYINT NOT NULL, sms_enabled TINYINT NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_207F257FA76ED395 (user_id), UNIQUE INDEX UNIQ_USER_NOTIFICATION_TYPE (user_id, type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id BINARY(16) NOT NULL, fname VARCHAR(100) DEFAULT NULL, lname VARCHAR(100) DEFAULT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, city VARCHAR(100) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, avatar_file_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), INDEX IDX_1483A5E945A576B2 (avatar_file_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE asset_file ADD CONSTRAINT FK_68FBF3D85DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)');
        $this->addSql('ALTER TABLE asset_file ADD CONSTRAINT FK_68FBF3D893CB796C FOREIGN KEY (file_id) REFERENCES files (id)');
        $this->addSql('ALTER TABLE assets ADD CONSTRAINT FK_79D17D8E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE asset_task ADD CONSTRAINT FK_B61A1EED5DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE asset_task ADD CONSTRAINT FK_B61A1EED8DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_63540597E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE task_groups ADD CONSTRAINT FK_95FAE6E07E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE task_group_task ADD CONSTRAINT FK_6737CBFABE94330B FOREIGN KEY (task_group_id) REFERENCES task_groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_group_task ADD CONSTRAINT FK_6737CBFA8DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_users ADD CONSTRAINT FK_D327BEC98DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id)');
        $this->addSql('ALTER TABLE task_users ADD CONSTRAINT FK_D327BEC9A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597727ACA70 FOREIGN KEY (parent_id) REFERENCES tasks (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user_notification_preferences ADD CONSTRAINT FK_207F257FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E945A576B2 FOREIGN KEY (avatar_file_id) REFERENCES files (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE asset_file DROP FOREIGN KEY FK_68FBF3D85DA1941');
        $this->addSql('ALTER TABLE asset_file DROP FOREIGN KEY FK_68FBF3D893CB796C');
        $this->addSql('ALTER TABLE assets DROP FOREIGN KEY FK_79D17D8E7E3C61F9');
        $this->addSql('ALTER TABLE asset_task DROP FOREIGN KEY FK_B61A1EED5DA1941');
        $this->addSql('ALTER TABLE asset_task DROP FOREIGN KEY FK_B61A1EED8DB60186');
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_63540597E3C61F9');
        $this->addSql('ALTER TABLE task_groups DROP FOREIGN KEY FK_95FAE6E07E3C61F9');
        $this->addSql('ALTER TABLE task_group_task DROP FOREIGN KEY FK_6737CBFABE94330B');
        $this->addSql('ALTER TABLE task_group_task DROP FOREIGN KEY FK_6737CBFA8DB60186');
        $this->addSql('ALTER TABLE task_users DROP FOREIGN KEY FK_D327BEC98DB60186');
        $this->addSql('ALTER TABLE task_users DROP FOREIGN KEY FK_D327BEC9A76ED395');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597727ACA70');
        $this->addSql('ALTER TABLE user_notification_preferences DROP FOREIGN KEY FK_207F257FA76ED395');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E945A576B2');
        $this->addSql('DROP TABLE asset_file');
        $this->addSql('DROP TABLE assets');
        $this->addSql('DROP TABLE asset_task');
        $this->addSql('DROP TABLE files');
        $this->addSql('DROP TABLE task_groups');
        $this->addSql('DROP TABLE task_group_task');
        $this->addSql('DROP TABLE task_users');
        $this->addSql('DROP TABLE tasks');
        $this->addSql('DROP TABLE user_notification_preferences');
        $this->addSql('DROP TABLE users');
    }
}
