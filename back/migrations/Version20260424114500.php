<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424114500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop organizer request screening defaults to match Doctrine mapping';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE organizer_request ALTER screening_status DROP DEFAULT');
        $this->addSql('ALTER TABLE organizer_request ALTER screening_checks DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql("ALTER TABLE organizer_request ALTER screening_status SET DEFAULT 'NEEDS_REVIEW'");
        $this->addSql("ALTER TABLE organizer_request ALTER screening_checks SET DEFAULT '[]'");
    }
}
