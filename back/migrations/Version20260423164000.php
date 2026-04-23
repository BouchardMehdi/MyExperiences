<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423164000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed test accounts, organizer request, experiences and bookings for MyExperiences';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $passwordHash = '$2y$12$Ek6KyKuN.SLVVFVQgdhW1.dHgMHyoy/AD01f/Q29gMC8c9NNCZIMK';

        $this->addSql(<<<SQL
            INSERT INTO "user" (email, roles, password, firstname, lastname, created_at)
            SELECT 'admin@myexperiences.test', '["ROLE_ADMIN"]'::json, '{$passwordHash}', 'Alice', 'Admin', NOW() - INTERVAL '30 days'
            WHERE NOT EXISTS (SELECT 1 FROM "user" WHERE email = 'admin@myexperiences.test');
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO "user" (email, roles, password, firstname, lastname, created_at)
            SELECT 'organizer@myexperiences.test', '["ROLE_ORGANIZER"]'::json, '{$passwordHash}', 'Oscar', 'Organizer', NOW() - INTERVAL '25 days'
            WHERE NOT EXISTS (SELECT 1 FROM "user" WHERE email = 'organizer@myexperiences.test');
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO "user" (email, roles, password, firstname, lastname, created_at)
            SELECT 'studio@myexperiences.test', '["ROLE_ORGANIZER"]'::json, '{$passwordHash}', 'Sonia', 'Studio', NOW() - INTERVAL '22 days'
            WHERE NOT EXISTS (SELECT 1 FROM "user" WHERE email = 'studio@myexperiences.test');
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO "user" (email, roles, password, firstname, lastname, created_at)
            SELECT 'user@myexperiences.test', '["ROLE_USER"]'::json, '{$passwordHash}', 'Uma', 'User', NOW() - INTERVAL '20 days'
            WHERE NOT EXISTS (SELECT 1 FROM "user" WHERE email = 'user@myexperiences.test');
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO "user" (email, roles, password, firstname, lastname, created_at)
            SELECT 'traveler@myexperiences.test', '["ROLE_USER"]'::json, '{$passwordHash}', 'Theo', 'Traveler', NOW() - INTERVAL '16 days'
            WHERE NOT EXISTS (SELECT 1 FROM "user" WHERE email = 'traveler@myexperiences.test');
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO "user" (email, roles, password, firstname, lastname, created_at)
            SELECT 'candidate@myexperiences.test', '["ROLE_USER"]'::json, '{$passwordHash}', 'Camille', 'Candidate', NOW() - INTERVAL '8 days'
            WHERE NOT EXISTS (SELECT 1 FROM "user" WHERE email = 'candidate@myexperiences.test');
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO organizer_request (user_id, reviewed_by_id, motivation, status, created_at, processed_at)
            SELECT candidate.id, NULL, 'Je souhaite publier des experiences locales et proposer des ateliers bien cadres.', 'PENDING', NOW() - INTERVAL '2 days', NULL
            FROM "user" candidate
            WHERE candidate.email = 'candidate@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1
                FROM organizer_request request
                WHERE request.user_id = candidate.id
                  AND request.status = 'PENDING'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO experience (organizer_id, title, description, price, location, duration, status, created_at)
            SELECT organizer.id, 'Atelier Ceramique Sunrise', 'Un atelier ceramique au petit matin avec boisson chaude, modelage guide et fournee partagee.', 72.00, 'Lille', 120, 'PUBLISHED', NOW() - INTERVAL '18 days'
            FROM "user" organizer
            WHERE organizer.email = 'organizer@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM experience existing
                WHERE existing.organizer_id = organizer.id
                  AND existing.title = 'Atelier Ceramique Sunrise'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO experience (organizer_id, title, description, price, location, duration, status, created_at)
            SELECT organizer.id, 'Rooftop Photo Walk', 'Une marche photo au coucher du soleil avec coaching composition, reperage et portraits urbains.', 58.00, 'Paris', 150, 'PUBLISHED', NOW() - INTERVAL '14 days'
            FROM "user" organizer
            WHERE organizer.email = 'organizer@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM experience existing
                WHERE existing.organizer_id = organizer.id
                  AND existing.title = 'Rooftop Photo Walk'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO experience (organizer_id, title, description, price, location, duration, status, created_at)
            SELECT organizer.id, 'Surf and Brunch Biarritz', 'Session surf petit groupe puis brunch local face a l ocean pour prolonger la matinee.', 95.00, 'Biarritz', 180, 'PUBLISHED', NOW() - INTERVAL '12 days'
            FROM "user" organizer
            WHERE organizer.email = 'studio@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM experience existing
                WHERE existing.organizer_id = organizer.id
                  AND existing.title = 'Surf and Brunch Biarritz'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO experience (organizer_id, title, description, price, location, duration, status, created_at)
            SELECT organizer.id, 'Degustation Privee Lyon', 'Un format intimiste autour des accords mets et vins, encore en preparation avant publication.', 84.00, 'Lyon', 100, 'DRAFT', NOW() - INTERVAL '6 days'
            FROM "user" organizer
            WHERE organizer.email = 'studio@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM experience existing
                WHERE existing.organizer_id = organizer.id
                  AND existing.title = 'Degustation Privee Lyon'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO experience (organizer_id, title, description, price, location, duration, status, created_at)
            SELECT organizer.id, 'Yoga Boat Sunrise', 'Une pratique douce sur un bateau amarre, pensee pour les leve-tot et deja archivee.', 48.00, 'Marseille', 75, 'ARCHIVED', NOW() - INTERVAL '28 days'
            FROM "user" organizer
            WHERE organizer.email = 'organizer@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM experience existing
                WHERE existing.organizer_id = organizer.id
                  AND existing.title = 'Yoga Boat Sunrise'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT experience.id, NOW() - INTERVAL '12 days', NOW() - INTERVAL '12 days' + INTERVAL '2 hours', 10, 8, FALSE
            FROM experience
            WHERE experience.title = 'Atelier Ceramique Sunrise'
              AND NOT EXISTS (
                SELECT 1 FROM slot existing
                WHERE existing.experience_id = experience.id
                  AND existing.start_at = NOW() - INTERVAL '12 days'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT experience.id, NOW() + INTERVAL '4 days', NOW() + INTERVAL '4 days' + INTERVAL '2 hours', 12, 10, TRUE
            FROM experience
            WHERE experience.title = 'Atelier Ceramique Sunrise'
              AND NOT EXISTS (
                SELECT 1 FROM slot existing
                WHERE existing.experience_id = experience.id
                  AND existing.start_at = NOW() + INTERVAL '4 days'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT experience.id, NOW() + INTERVAL '14 days', NOW() + INTERVAL '14 days' + INTERVAL '2 hours', 10, 10, TRUE
            FROM experience
            WHERE experience.title = 'Atelier Ceramique Sunrise'
              AND NOT EXISTS (
                SELECT 1 FROM slot existing
                WHERE existing.experience_id = experience.id
                  AND existing.start_at = NOW() + INTERVAL '14 days'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT experience.id, NOW() + INTERVAL '7 days', NOW() + INTERVAL '7 days' + INTERVAL '150 minutes', 14, 13, TRUE
            FROM experience
            WHERE experience.title = 'Rooftop Photo Walk'
              AND NOT EXISTS (
                SELECT 1 FROM slot existing
                WHERE existing.experience_id = experience.id
                  AND existing.start_at = NOW() + INTERVAL '7 days'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT experience.id, NOW() + INTERVAL '21 days', NOW() + INTERVAL '21 days' + INTERVAL '150 minutes', 12, 12, TRUE
            FROM experience
            WHERE experience.title = 'Rooftop Photo Walk'
              AND NOT EXISTS (
                SELECT 1 FROM slot existing
                WHERE existing.experience_id = experience.id
                  AND existing.start_at = NOW() + INTERVAL '21 days'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT experience.id, NOW() - INTERVAL '5 days', NOW() - INTERVAL '5 days' + INTERVAL '3 hours', 8, 7, FALSE
            FROM experience
            WHERE experience.title = 'Surf and Brunch Biarritz'
              AND NOT EXISTS (
                SELECT 1 FROM slot existing
                WHERE existing.experience_id = experience.id
                  AND existing.start_at = NOW() - INTERVAL '5 days'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT experience.id, NOW() + INTERVAL '9 days', NOW() + INTERVAL '9 days' + INTERVAL '3 hours', 8, 8, TRUE
            FROM experience
            WHERE experience.title = 'Surf and Brunch Biarritz'
              AND NOT EXISTS (
                SELECT 1 FROM slot existing
                WHERE existing.experience_id = experience.id
                  AND existing.start_at = NOW() + INTERVAL '9 days'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT experience.id, NOW() + INTERVAL '18 days', NOW() + INTERVAL '18 days' + INTERVAL '100 minutes', 10, 10, TRUE
            FROM experience
            WHERE experience.title = 'Degustation Privee Lyon'
              AND NOT EXISTS (
                SELECT 1 FROM slot existing
                WHERE existing.experience_id = experience.id
                  AND existing.start_at = NOW() + INTERVAL '18 days'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT experience.id, NOW() - INTERVAL '20 days', NOW() - INTERVAL '20 days' + INTERVAL '75 minutes', 16, 16, FALSE
            FROM experience
            WHERE experience.title = 'Yoga Boat Sunrise'
              AND NOT EXISTS (
                SELECT 1 FROM slot existing
                WHERE existing.experience_id = experience.id
                  AND existing.start_at = NOW() - INTERVAL '20 days'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO booking (user_id, slot_id, status, seats, total_price, created_at)
            SELECT customer.id, slot.id, 'PENDING', 2, 144.00, NOW() - INTERVAL '1 day'
            FROM "user" customer
            INNER JOIN experience ON experience.title = 'Atelier Ceramique Sunrise'
            INNER JOIN slot ON slot.experience_id = experience.id AND slot.start_at = NOW() + INTERVAL '4 days'
            WHERE customer.email = 'user@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM booking existing
                WHERE existing.user_id = customer.id
                  AND existing.slot_id = slot.id
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO booking (user_id, slot_id, status, seats, total_price, created_at)
            SELECT customer.id, slot.id, 'PAID', 1, 95.00, NOW() - INTERVAL '6 days'
            FROM "user" customer
            INNER JOIN experience ON experience.title = 'Surf and Brunch Biarritz'
            INNER JOIN slot ON slot.experience_id = experience.id AND slot.start_at = NOW() - INTERVAL '5 days'
            WHERE customer.email = 'user@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM booking existing
                WHERE existing.user_id = customer.id
                  AND existing.slot_id = slot.id
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO booking (user_id, slot_id, status, seats, total_price, created_at)
            SELECT customer.id, slot.id, 'PAID', 1, 58.00, NOW() - INTERVAL '2 days'
            FROM "user" customer
            INNER JOIN experience ON experience.title = 'Rooftop Photo Walk'
            INNER JOIN slot ON slot.experience_id = experience.id AND slot.start_at = NOW() + INTERVAL '7 days'
            WHERE customer.email = 'traveler@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM booking existing
                WHERE existing.user_id = customer.id
                  AND existing.slot_id = slot.id
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO booking (user_id, slot_id, status, seats, total_price, created_at)
            SELECT customer.id, slot.id, 'CANCELLED', 1, 48.00, NOW() - INTERVAL '19 days'
            FROM "user" customer
            INNER JOIN experience ON experience.title = 'Yoga Boat Sunrise'
            INNER JOIN slot ON slot.experience_id = experience.id AND slot.start_at = NOW() - INTERVAL '20 days'
            WHERE customer.email = 'traveler@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM booking existing
                WHERE existing.user_id = customer.id
                  AND existing.slot_id = slot.id
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO payment (booking_id, amount, status, provider, transaction_ref, created_at)
            SELECT booking.id, 95.00, 'SUCCESS', 'mock', 'seed_payment_success_user_surf', NOW() - INTERVAL '5 days'
            FROM booking
            INNER JOIN "user" customer ON customer.id = booking.user_id
            INNER JOIN slot ON slot.id = booking.slot_id
            INNER JOIN experience ON experience.id = slot.experience_id
            WHERE customer.email = 'user@myexperiences.test'
              AND experience.title = 'Surf and Brunch Biarritz'
              AND NOT EXISTS (
                SELECT 1 FROM payment existing
                WHERE existing.transaction_ref = 'seed_payment_success_user_surf'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO payment (booking_id, amount, status, provider, transaction_ref, created_at)
            SELECT booking.id, 58.00, 'SUCCESS', 'mock', 'seed_payment_success_traveler_photo', NOW() - INTERVAL '2 days'
            FROM booking
            INNER JOIN "user" customer ON customer.id = booking.user_id
            INNER JOIN slot ON slot.id = booking.slot_id
            INNER JOIN experience ON experience.id = slot.experience_id
            WHERE customer.email = 'traveler@myexperiences.test'
              AND experience.title = 'Rooftop Photo Walk'
              AND NOT EXISTS (
                SELECT 1 FROM payment existing
                WHERE existing.transaction_ref = 'seed_payment_success_traveler_photo'
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO review (user_id, experience_id, rating, comment, created_at)
            SELECT customer.id, experience.id, 5, 'Un format parfait pour tester une sortie active sans logistique compliquee.', NOW() - INTERVAL '4 days'
            FROM "user" customer
            INNER JOIN experience ON experience.title = 'Surf and Brunch Biarritz'
            WHERE customer.email = 'user@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM review existing
                WHERE existing.user_id = customer.id
                  AND existing.experience_id = experience.id
              );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO review (user_id, experience_id, rating, comment, created_at)
            SELECT customer.id, experience.id, 4, 'Une marche photo tres bien guidee, avec un groupe de taille ideale.', NOW() - INTERVAL '1 day'
            FROM "user" customer
            INNER JOIN experience ON experience.title = 'Rooftop Photo Walk'
            WHERE customer.email = 'traveler@myexperiences.test'
              AND NOT EXISTS (
                SELECT 1 FROM review existing
                WHERE existing.user_id = customer.id
                  AND existing.experience_id = experience.id
              );
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql(<<<SQL
            DELETE FROM "user"
            WHERE email IN (
                'admin@myexperiences.test',
                'organizer@myexperiences.test',
                'studio@myexperiences.test',
                'user@myexperiences.test',
                'traveler@myexperiences.test',
                'candidate@myexperiences.test'
            );
        SQL);
    }
}
