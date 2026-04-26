<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260426153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed a large portfolio dataset with many users, organizers, experiences, bookings and reviews';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $adminPasswordHash = '$2y$12$SKX.GZPqcxbIsJNEdUmfPuyyDikLGNqCgTGl1XE/Iv4Nyi7gUvAS.';
        $organizerPasswordHash = '$2y$12$DgkIA/lFCMsmSjG9D.KFXu0DkdA9zHXgdWEhkrpzsjttAtdasz4BG';
        $userPasswordHash = '$2y$12$NSY7MGFtbr6gqTKFG5ucS.S7oYQ3PEODEurbCFwcX8//i8XHHltsC';

        $this->addSql(<<<SQL
            INSERT INTO "user" (email, roles, password, firstname, lastname, created_at)
            VALUES
                ('portfolio.admin@myexperiences.test', '["ROLE_ADMIN"]'::json, '{$adminPasswordHash}', 'Adele', 'Portfolio', NOW() - INTERVAL '90 days'),
                ('portfolio.organizer@myexperiences.test', '["ROLE_ORGANIZER"]'::json, '{$organizerPasswordHash}', 'Nolan', 'Organisateur', NOW() - INTERVAL '88 days')
            ON CONFLICT (email) DO UPDATE
            SET roles = EXCLUDED.roles,
                password = EXCLUDED.password,
                firstname = EXCLUDED.firstname,
                lastname = EXCLUDED.lastname;
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO "user" (email, roles, password, firstname, lastname, created_at)
            SELECT
                'portfolio.organizer' || lpad(index::text, 2, '0') || '@myexperiences.test',
                '["ROLE_ORGANIZER"]'::json,
                '{$organizerPasswordHash}',
                (ARRAY['Mila','Adam','Iris','Leo','Nora','Hugo','Lina','Noe','Jade','Eliott','Rose','Mael','Lou','Sacha','Ines','Tom','Anna','Nino','Lea','Oscar','Zoé','Romy','Gabin','Ella'])[index],
                (ARRAY['Ateliers','Culture','Escapades','Studio','Collectif','Voyages','Moments','Decouverte','Urbain','Nature','Cuisine','Photo','Bienetre','Aventure','Famille','Creation','Local','Scene','Rivage','Montagne','Lumiere','Partage','Horizon','Agora'])[index],
                NOW() - ((95 - index) || ' days')::interval
            FROM generate_series(1, 24) AS index
            ON CONFLICT (email) DO NOTHING;
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO "user" (email, roles, password, firstname, lastname, created_at)
            SELECT
                'portfolio.user' || lpad(index::text, 3, '0') || '@myexperiences.test',
                '["ROLE_USER"]'::json,
                '{$userPasswordHash}',
                (ARRAY['Camille','Mathis','Sarah','Lucas','Emma','Louis','Chloe','Nathan','Manon','Jules'])[1 + ((index - 1) % 10)],
                (ARRAY['Martin','Bernard','Petit','Robert','Richard','Durand','Moreau','Simon','Laurent','Lefevre'])[1 + ((index - 1) % 10)],
                NOW() - ((80 - (index % 45)) || ' days')::interval
            FROM generate_series(1, 90) AS index
            ON CONFLICT (email) DO NOTHING;
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO organizer_request (
                user_id,
                reviewed_by_id,
                motivation,
                status,
                created_at,
                processed_at,
                organization_name,
                phone_number,
                street_address,
                postal_code,
                city,
                country,
                business_type,
                event_types,
                activity_description,
                website_url,
                social_links,
                siret,
                screening_status,
                screening_checks
            )
            SELECT
                organizer.id,
                admin.id,
                'Dossier portfolio pre-valide pour montrer le workflow organisateur.',
                'APPROVED',
                organizer.created_at + INTERVAL '2 days',
                organizer.created_at + INTERVAL '3 days',
                'Portfolio ' || organizer.firstname || ' ' || organizer.lastname,
                '+3360000' || lpad((row_number() OVER (ORDER BY organizer.email))::text, 4, '0'),
                (12 + row_number() OVER (ORDER BY organizer.email)) || ' rue des Experiences',
                (ARRAY['75001','69002','33000','59000','44000','13002','67000','31000'])[1 + ((row_number() OVER (ORDER BY organizer.email) - 1) % 8)],
                (ARRAY['Paris','Lyon','Bordeaux','Lille','Nantes','Marseille','Strasbourg','Toulouse'])[1 + ((row_number() OVER (ORDER BY organizer.email) - 1) % 8)],
                'France',
                (ARRAY['INDIVIDUAL','COMPANY','ASSOCIATION','COLLECTIVE'])[1 + ((row_number() OVER (ORDER BY organizer.email) - 1) % 4)],
                '["WORKSHOP","CULTURE","FOOD"]'::json,
                'Organisateur de demonstration avec des experiences variees, des creneaux et des reservations pour enrichir le portfolio.',
                'https://experiences.bouchard-mehdi.fr',
                '@myexperiences.portfolio',
                '732829320' || lpad((10000 + row_number() OVER (ORDER BY organizer.email))::text, 5, '0'),
                'PRE_VALIDATED',
                '[{"label":"Profil complet","status":"valid"},{"label":"Adresse coherente","status":"valid"},{"label":"Contact fourni","status":"valid"}]'::json
            FROM "user" organizer
            CROSS JOIN "user" admin
            WHERE organizer.email LIKE 'portfolio.organizer%'
              AND admin.email = 'portfolio.admin@myexperiences.test'
              AND NOT EXISTS (
                  SELECT 1
                  FROM organizer_request existing
                  WHERE existing.user_id = organizer.id
              );
        SQL);

        $this->addSql(<<<'SQL'
            DO $$
            DECLARE
                index integer;
                organizer_id integer;
                title_value text;
                city_value text;
                category_value text;
                description_value text;
                latitude_value numeric(9, 6);
                longitude_value numeric(9, 6);
                status_value text;
            BEGIN
                FOR index IN 1..260 LOOP
                    SELECT id INTO organizer_id
                    FROM "user"
                    WHERE email IN (
                        'portfolio.organizer@myexperiences.test',
                        'portfolio.organizer' || lpad((1 + ((index - 1) % 24))::text, 2, '0') || '@myexperiences.test'
                    )
                    ORDER BY email DESC
                    LIMIT 1;

                    city_value := (ARRAY['Paris','Lyon','Bordeaux','Lille','Nantes','Marseille','Strasbourg','Toulouse','Biarritz','Annecy','Rennes','Montpellier'])[1 + ((index - 1) % 12)];
                    category_value := (ARRAY['atelier creatif','balade photo','degustation locale','initiation sportive','session bien-etre','sortie famille','soiree culturelle','escape nature','brunch secret','visite artisanale'])[1 + ((index - 1) % 10)];
                    title_value := 'Portfolio ' || lpad(index::text, 3, '0') || ' - ' || initcap(category_value) || ' a ' || city_value;
                    status_value := CASE
                        WHEN index % 17 = 0 THEN 'ARCHIVED'
                        WHEN index % 11 = 0 THEN 'DRAFT'
                        ELSE 'PUBLISHED'
                    END;
                    description_value := 'Experience de demonstration pour le portfolio MyExperiences : ' || category_value || ' en petit groupe a ' || city_value || ', avec un parcours clair, des creneaux realistes et des reservations de test.';

                    latitude_value := CASE city_value
                        WHEN 'Paris' THEN 48.856614
                        WHEN 'Lyon' THEN 45.764043
                        WHEN 'Bordeaux' THEN 44.837789
                        WHEN 'Lille' THEN 50.629250
                        WHEN 'Nantes' THEN 47.218371
                        WHEN 'Marseille' THEN 43.296482
                        WHEN 'Strasbourg' THEN 48.573405
                        WHEN 'Toulouse' THEN 43.604652
                        WHEN 'Biarritz' THEN 43.483151
                        WHEN 'Annecy' THEN 45.899247
                        WHEN 'Rennes' THEN 48.117266
                        ELSE 43.610769
                    END + ((index % 9) - 4) * 0.006;

                    longitude_value := CASE city_value
                        WHEN 'Paris' THEN 2.352222
                        WHEN 'Lyon' THEN 4.835659
                        WHEN 'Bordeaux' THEN -0.579180
                        WHEN 'Lille' THEN 3.057256
                        WHEN 'Nantes' THEN -1.553621
                        WHEN 'Marseille' THEN 5.369780
                        WHEN 'Strasbourg' THEN 7.752111
                        WHEN 'Toulouse' THEN 1.444209
                        WHEN 'Biarritz' THEN -1.558626
                        WHEN 'Annecy' THEN 6.129384
                        WHEN 'Rennes' THEN -1.677793
                        ELSE 3.876716
                    END + ((index % 7) - 3) * 0.006;

                    INSERT INTO experience (
                        organizer_id,
                        title,
                        description,
                        price,
                        location,
                        duration,
                        status,
                        created_at,
                        latitude,
                        longitude
                    )
                    SELECT
                        organizer_id,
                        title_value,
                        description_value,
                        24 + ((index * 7) % 135),
                        city_value,
                        60 + ((index % 6) * 30),
                        status_value,
                        NOW() - ((75 - (index % 60)) || ' days')::interval,
                        latitude_value,
                        longitude_value
                    WHERE NOT EXISTS (
                        SELECT 1
                        FROM experience existing
                        WHERE existing.title = title_value
                    );
                END LOOP;
            END $$;
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO slot (experience_id, start_at, end_at, capacity, remaining_places, is_active)
            SELECT
                experience.id,
                NOW()
                    + (((slot_index * 7) + (experience.id % 9) - 21) || ' days')::interval
                    + (((experience.id + slot_index) % 8 + 9) || ' hours')::interval,
                NOW()
                    + (((slot_index * 7) + (experience.id % 9) - 21) || ' days')::interval
                    + (((experience.id + slot_index) % 8 + 9) || ' hours')::interval
                    + (experience.duration || ' minutes')::interval,
                8 + ((experience.id + slot_index) % 12),
                8 + ((experience.id + slot_index) % 12),
                CASE WHEN experience.status = 'PUBLISHED' THEN TRUE ELSE FALSE END
            FROM experience
            CROSS JOIN generate_series(1, 4) AS slot_index
            WHERE experience.title LIKE 'Portfolio %'
              AND NOT EXISTS (
                  SELECT 1
                  FROM slot existing
                  WHERE existing.experience_id = experience.id
                    AND existing.start_at = NOW()
                        + (((slot_index * 7) + (experience.id % 9) - 21) || ' days')::interval
                        + (((experience.id + slot_index) % 8 + 9) || ' hours')::interval
              );
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO booking (user_id, slot_id, status, seats, total_price, created_at)
            SELECT
                customer.id,
                selected_slots.id,
                CASE
                    WHEN selected_slots.row_number % 9 = 0 THEN 'CANCELLED'
                    WHEN selected_slots.start_at > NOW() AND selected_slots.row_number % 5 = 0 THEN 'PENDING'
                    ELSE 'PAID'
                END,
                1 + (selected_slots.row_number % 3),
                (1 + (selected_slots.row_number % 3)) * selected_slots.price,
                selected_slots.start_at - INTERVAL '5 days'
            FROM (
                SELECT
                    slot.id,
                    slot.start_at,
                    experience.price,
                    row_number() OVER (ORDER BY slot.start_at, slot.id) AS row_number
                FROM slot
                INNER JOIN experience ON experience.id = slot.experience_id
                WHERE experience.title LIKE 'Portfolio %'
                  AND experience.status = 'PUBLISHED'
                LIMIT 720
            ) selected_slots
            INNER JOIN "user" customer
                ON customer.email = 'portfolio.user' || lpad((1 + ((selected_slots.row_number - 1) % 90))::text, 3, '0') || '@myexperiences.test'
            WHERE NOT EXISTS (
                SELECT 1
                FROM booking existing
                WHERE existing.user_id = customer.id
                  AND existing.slot_id = selected_slots.id
            );
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO payment (booking_id, amount, status, provider, transaction_ref, created_at)
            SELECT
                booking.id,
                booking.total_price,
                'SUCCESS',
                'mock',
                'portfolio_payment_success_' || booking.id,
                booking.created_at + INTERVAL '8 minutes'
            FROM booking
            INNER JOIN slot ON slot.id = booking.slot_id
            INNER JOIN experience ON experience.id = slot.experience_id
            WHERE experience.title LIKE 'Portfolio %'
              AND booking.status = 'PAID'
              AND NOT EXISTS (
                  SELECT 1
                  FROM payment existing
                  WHERE existing.booking_id = booking.id
              );
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO payment (booking_id, amount, status, provider, transaction_ref, created_at)
            SELECT
                booking.id,
                booking.total_price,
                'FAILED',
                'mock',
                'portfolio_payment_failed_' || booking.id,
                booking.created_at + INTERVAL '5 minutes'
            FROM booking
            INNER JOIN slot ON slot.id = booking.slot_id
            INNER JOIN experience ON experience.id = slot.experience_id
            WHERE experience.title LIKE 'Portfolio %'
              AND booking.status = 'CANCELLED'
              AND NOT EXISTS (
                  SELECT 1
                  FROM payment existing
                  WHERE existing.booking_id = booking.id
              );
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO review (user_id, experience_id, rating, comment, created_at)
            SELECT DISTINCT ON (customer.id, experience.id)
                customer.id,
                experience.id,
                3 + (selected_experiences.row_number % 3),
                (ARRAY[
                    'Experience tres claire pour comprendre le parcours de reservation.',
                    'Bon format, organisateur reactif et fiche experience rassurante.',
                    'La page donne envie de reserver et le paiement mock est facile a tester.',
                    'Parfait pour se projeter dans une vraie marketplace locale.',
                    'Les informations sont completes et les creneaux faciles a lire.'
                ])[1 + ((selected_experiences.row_number - 1) % 5)],
                NOW() - ((selected_experiences.row_number % 35) || ' days')::interval
            FROM (
                SELECT
                    experience.id,
                    row_number() OVER (ORDER BY experience.id) AS row_number
                FROM experience
                WHERE experience.title LIKE 'Portfolio %'
                  AND experience.status = 'PUBLISHED'
                LIMIT 220
            ) selected_experiences
            INNER JOIN experience ON experience.id = selected_experiences.id
            INNER JOIN "user" customer
                ON customer.email = 'portfolio.user' || lpad((1 + ((selected_experiences.row_number * 7) % 90))::text, 3, '0') || '@myexperiences.test'
            WHERE NOT EXISTS (
                SELECT 1
                FROM review existing
                WHERE existing.user_id = customer.id
                  AND existing.experience_id = experience.id
            );
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE slot
            SET remaining_places = GREATEST(
                0,
                slot.capacity - COALESCE(booking_totals.booked_seats, 0)
            )
            FROM (
                SELECT
                    booking.slot_id,
                    SUM(booking.seats) AS booked_seats
                FROM booking
                WHERE booking.status != 'CANCELLED'
                GROUP BY booking.slot_id
            ) booking_totals
            WHERE slot.id = booking_totals.slot_id;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql("DELETE FROM \"user\" WHERE email LIKE 'portfolio.%@myexperiences.test'");
    }
}
