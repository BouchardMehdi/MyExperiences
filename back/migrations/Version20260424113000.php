<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add screening fields to organizer requests for automated pre-validation';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql("ALTER TABLE organizer_request ADD screening_status VARCHAR(20) DEFAULT 'NEEDS_REVIEW' NOT NULL");
        $this->addSql("ALTER TABLE organizer_request ADD screening_checks JSON DEFAULT '[]' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE organizer_request DROP screening_status');
        $this->addSql('ALTER TABLE organizer_request DROP screening_checks');
    }
}
