<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add coordinates to experiences and seed known locations for the map view';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE experience ADD latitude NUMERIC(9, 6) DEFAULT NULL');
        $this->addSql('ALTER TABLE experience ADD longitude NUMERIC(9, 6) DEFAULT NULL');

        $this->addSql("UPDATE experience SET latitude = 50.629250, longitude = 3.057256 WHERE title = 'Atelier Ceramique Sunrise'");
        $this->addSql("UPDATE experience SET latitude = 48.856614, longitude = 2.352222 WHERE title = 'Rooftop Photo Walk'");
        $this->addSql("UPDATE experience SET latitude = 43.483151, longitude = -1.558626 WHERE title = 'Surf and Brunch Biarritz'");
        $this->addSql("UPDATE experience SET latitude = 45.764043, longitude = 4.835659 WHERE title = 'Degustation Privee Lyon'");
        $this->addSql("UPDATE experience SET latitude = 43.296482, longitude = 5.369780 WHERE title = 'Yoga Boat Sunrise'");

        $this->addSql("UPDATE experience SET latitude = 50.629250, longitude = 3.057256 WHERE latitude IS NULL AND longitude IS NULL AND location = 'Lille'");
        $this->addSql("UPDATE experience SET latitude = 48.856614, longitude = 2.352222 WHERE latitude IS NULL AND longitude IS NULL AND location = 'Paris'");
        $this->addSql("UPDATE experience SET latitude = 43.483151, longitude = -1.558626 WHERE latitude IS NULL AND longitude IS NULL AND location = 'Biarritz'");
        $this->addSql("UPDATE experience SET latitude = 45.764043, longitude = 4.835659 WHERE latitude IS NULL AND longitude IS NULL AND location = 'Lyon'");
        $this->addSql("UPDATE experience SET latitude = 43.296482, longitude = 5.369780 WHERE latitude IS NULL AND longitude IS NULL AND location = 'Marseille'");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE experience DROP latitude');
        $this->addSql('ALTER TABLE experience DROP longitude');
    }
}
