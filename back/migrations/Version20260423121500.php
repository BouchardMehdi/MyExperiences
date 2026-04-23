<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add query indexes for public experiences and booking workflows';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_booking_user_status_created_at ON booking (user_id, status, created_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_experience_public_filters ON experience (status, location, price)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_payment_booking_status ON payment (booking_id, status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_review_experience_created_at ON review (experience_id, created_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_slot_experience_start_at ON slot (experience_id, start_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_slot_active_start_at ON slot (is_active, start_at)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('DROP INDEX IF EXISTS idx_booking_user_status_created_at');
        $this->addSql('DROP INDEX IF EXISTS idx_experience_public_filters');
        $this->addSql('DROP INDEX IF EXISTS idx_payment_booking_status');
        $this->addSql('DROP INDEX IF EXISTS idx_review_experience_created_at');
        $this->addSql('DROP INDEX IF EXISTS idx_slot_experience_start_at');
        $this->addSql('DROP INDEX IF EXISTS idx_slot_active_start_at');
    }
}
