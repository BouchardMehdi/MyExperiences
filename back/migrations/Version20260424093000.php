<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand organizer requests with business and contact information';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE organizer_request ADD organization_name VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD phone_number VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD street_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD postal_code VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD city VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD country VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD business_type VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD event_types JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD activity_description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD website_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD social_links TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE organizer_request ADD siret VARCHAR(14) DEFAULT NULL');

        $this->addSql(<<<'SQL'
            UPDATE organizer_request
            SET
                organization_name = COALESCE(organization_name, 'Studio des Experiences'),
                phone_number = COALESCE(phone_number, '+33612345678'),
                street_address = COALESCE(street_address, '12 rue des Ateliers'),
                postal_code = COALESCE(postal_code, '59000'),
                city = COALESCE(city, 'Lille'),
                country = COALESCE(country, 'France'),
                business_type = COALESCE(business_type, 'COMPANY'),
                event_types = COALESCE(event_types, '["WORKSHOP","CULTURE"]'),
                activity_description = COALESCE(activity_description, 'Nous organisons des ateliers creatifs et des experiences culturelles en petit groupe avec un accompagnement professionnel.'),
                website_url = website_url,
                social_links = COALESCE(social_links, '@studio.experiences'),
                siret = COALESCE(siret, '12345678900011')
        SQL);

        $this->addSql('ALTER TABLE organizer_request ALTER organization_name SET NOT NULL');
        $this->addSql('ALTER TABLE organizer_request ALTER phone_number SET NOT NULL');
        $this->addSql('ALTER TABLE organizer_request ALTER street_address SET NOT NULL');
        $this->addSql('ALTER TABLE organizer_request ALTER postal_code SET NOT NULL');
        $this->addSql('ALTER TABLE organizer_request ALTER city SET NOT NULL');
        $this->addSql('ALTER TABLE organizer_request ALTER country SET NOT NULL');
        $this->addSql('ALTER TABLE organizer_request ALTER business_type SET NOT NULL');
        $this->addSql('ALTER TABLE organizer_request ALTER event_types SET NOT NULL');
        $this->addSql('ALTER TABLE organizer_request ALTER activity_description SET NOT NULL');
        $this->addSql('ALTER TABLE organizer_request ALTER siret SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE organizer_request DROP organization_name');
        $this->addSql('ALTER TABLE organizer_request DROP phone_number');
        $this->addSql('ALTER TABLE organizer_request DROP street_address');
        $this->addSql('ALTER TABLE organizer_request DROP postal_code');
        $this->addSql('ALTER TABLE organizer_request DROP city');
        $this->addSql('ALTER TABLE organizer_request DROP country');
        $this->addSql('ALTER TABLE organizer_request DROP business_type');
        $this->addSql('ALTER TABLE organizer_request DROP event_types');
        $this->addSql('ALTER TABLE organizer_request DROP activity_description');
        $this->addSql('ALTER TABLE organizer_request DROP website_url');
        $this->addSql('ALTER TABLE organizer_request DROP social_links');
        $this->addSql('ALTER TABLE organizer_request DROP siret');
    }
}
