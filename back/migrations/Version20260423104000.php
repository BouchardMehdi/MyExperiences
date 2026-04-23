<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423104000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create MyExperiences core tables';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $userTable = $schema->createTable('user');
        $userTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $userTable->addColumn('email', 'string', ['length' => 180]);
        $userTable->addColumn('roles', 'json');
        $userTable->addColumn('password', 'string', ['length' => 255]);
        $userTable->addColumn('firstname', 'string', ['length' => 100]);
        $userTable->addColumn('lastname', 'string', ['length' => 100]);
        $userTable->addColumn('created_at', 'datetime_immutable');
        $userTable->setPrimaryKey(['id']);
        $userTable->addUniqueIndex(['email'], 'uniq_user_email');

        $experienceTable = $schema->createTable('experience');
        $experienceTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $experienceTable->addColumn('organizer_id', 'integer');
        $experienceTable->addColumn('title', 'string', ['length' => 255]);
        $experienceTable->addColumn('description', 'text');
        $experienceTable->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2]);
        $experienceTable->addColumn('location', 'string', ['length' => 255]);
        $experienceTable->addColumn('duration', 'integer');
        $experienceTable->addColumn('status', 'string', ['length' => 20]);
        $experienceTable->addColumn('created_at', 'datetime_immutable');
        $experienceTable->setPrimaryKey(['id']);
        $experienceTable->addIndex(['organizer_id'], 'idx_experience_organizer');
        $experienceTable->addForeignKeyConstraint('user', ['organizer_id'], ['id'], ['onDelete' => 'CASCADE']);

        $slotTable = $schema->createTable('slot');
        $slotTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $slotTable->addColumn('experience_id', 'integer');
        $slotTable->addColumn('start_at', 'datetime_immutable');
        $slotTable->addColumn('end_at', 'datetime_immutable');
        $slotTable->addColumn('capacity', 'integer');
        $slotTable->addColumn('remaining_places', 'integer');
        $slotTable->addColumn('is_active', 'boolean');
        $slotTable->setPrimaryKey(['id']);
        $slotTable->addIndex(['experience_id'], 'idx_slot_experience');
        $slotTable->addForeignKeyConstraint('experience', ['experience_id'], ['id'], ['onDelete' => 'CASCADE']);

        $bookingTable = $schema->createTable('booking');
        $bookingTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $bookingTable->addColumn('user_id', 'integer');
        $bookingTable->addColumn('slot_id', 'integer');
        $bookingTable->addColumn('status', 'string', ['length' => 20]);
        $bookingTable->addColumn('seats', 'integer');
        $bookingTable->addColumn('total_price', 'decimal', ['precision' => 10, 'scale' => 2]);
        $bookingTable->addColumn('created_at', 'datetime_immutable');
        $bookingTable->setPrimaryKey(['id']);
        $bookingTable->addIndex(['user_id'], 'idx_booking_user');
        $bookingTable->addIndex(['slot_id'], 'idx_booking_slot');
        $bookingTable->addForeignKeyConstraint('user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $bookingTable->addForeignKeyConstraint('slot', ['slot_id'], ['id'], ['onDelete' => 'CASCADE']);

        $paymentTable = $schema->createTable('payment');
        $paymentTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $paymentTable->addColumn('booking_id', 'integer');
        $paymentTable->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2]);
        $paymentTable->addColumn('status', 'string', ['length' => 20]);
        $paymentTable->addColumn('provider', 'string', ['length' => 50]);
        $paymentTable->addColumn('transaction_ref', 'string', ['length' => 100]);
        $paymentTable->addColumn('created_at', 'datetime_immutable');
        $paymentTable->setPrimaryKey(['id']);
        $paymentTable->addIndex(['booking_id'], 'idx_payment_booking');
        $paymentTable->addUniqueIndex(['transaction_ref'], 'uniq_payment_ref');
        $paymentTable->addForeignKeyConstraint('booking', ['booking_id'], ['id'], ['onDelete' => 'CASCADE']);

        $reviewTable = $schema->createTable('review');
        $reviewTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $reviewTable->addColumn('user_id', 'integer');
        $reviewTable->addColumn('experience_id', 'integer');
        $reviewTable->addColumn('rating', 'integer');
        $reviewTable->addColumn('comment', 'text');
        $reviewTable->addColumn('created_at', 'datetime_immutable');
        $reviewTable->setPrimaryKey(['id']);
        $reviewTable->addIndex(['user_id'], 'idx_review_user');
        $reviewTable->addIndex(['experience_id'], 'idx_review_experience');
        $reviewTable->addUniqueIndex(['user_id', 'experience_id'], 'uniq_review_user_experience');
        $reviewTable->addForeignKeyConstraint('user', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $reviewTable->addForeignKeyConstraint('experience', ['experience_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $schema->dropTable('review');
        $schema->dropTable('payment');
        $schema->dropTable('booking');
        $schema->dropTable('slot');
        $schema->dropTable('experience');
        $schema->dropTable('user');
    }
}
