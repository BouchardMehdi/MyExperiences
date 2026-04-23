<?php

namespace App\DataFixtures;

use App\Entity\Booking;
use App\Entity\Experience;
use App\Entity\Payment;
use App\Entity\Review;
use App\Entity\Slot;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Enum\ExperienceStatus;
use App\Enum\PaymentStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setFirstname('Alice')
            ->setLastname('Admin')
            ->setEmail('admin@myexperiences.test')
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));

        $organizer = (new User())
            ->setFirstname('Oscar')
            ->setLastname('Organizer')
            ->setEmail('organizer@myexperiences.test')
            ->setRoles(['ROLE_ORGANIZER']);
        $organizer->setPassword($this->passwordHasher->hashPassword($organizer, 'password'));

        $traveler = (new User())
            ->setFirstname('Uma')
            ->setLastname('User')
            ->setEmail('user@myexperiences.test')
            ->setRoles(['ROLE_USER']);
        $traveler->setPassword($this->passwordHasher->hashPassword($traveler, 'password'));

        $publishedExperience = (new Experience())
            ->setOrganizer($organizer)
            ->setTitle('Atelier céramique au lever du jour')
            ->setDescription('Initiez-vous à la céramique dans un atelier intimiste, avec petit déjeuner local et création guidée de votre première pièce.')
            ->setPrice('65.00')
            ->setLocation('Lille')
            ->setDuration(120)
            ->setStatus(ExperienceStatus::PUBLISHED);

        $draftExperience = (new Experience())
            ->setOrganizer($organizer)
            ->setTitle('Randonnée photo urbaine')
            ->setDescription('Parcours photo dans la ville avec conseils de composition, repérage de lumière et mise en pratique sur plusieurs spots.')
            ->setPrice('35.00')
            ->setLocation('Paris')
            ->setDuration(180)
            ->setStatus(ExperienceStatus::DRAFT);

        $archivedExperience = (new Experience())
            ->setOrganizer($organizer)
            ->setTitle('Dégustation accords mets et thés')
            ->setDescription('Expérience gastronomique autour des accords subtils entre thés d’exception, douceurs sucrées et bouchées salées.')
            ->setPrice('49.00')
            ->setLocation('Lyon')
            ->setDuration(90)
            ->setStatus(ExperienceStatus::ARCHIVED);

        $futureSlot = (new Slot())
            ->setExperience($publishedExperience)
            ->setStartAt(new \DateTimeImmutable('+5 days 18:00'))
            ->setEndAt(new \DateTimeImmutable('+5 days 20:00'))
            ->setCapacity(10)
            ->setIsActive(true);

        $secondFutureSlot = (new Slot())
            ->setExperience($publishedExperience)
            ->setStartAt(new \DateTimeImmutable('+12 days 10:00'))
            ->setEndAt(new \DateTimeImmutable('+12 days 12:00'))
            ->setCapacity(8)
            ->setIsActive(true);

        $pastSlot = (new Slot())
            ->setExperience($publishedExperience)
            ->setStartAt(new \DateTimeImmutable('-10 days 14:00'))
            ->setEndAt(new \DateTimeImmutable('-10 days 16:00'))
            ->setCapacity(8)
            ->setIsActive(false)
            ->setRemainingPlaces(6);

        $draftSlot = (new Slot())
            ->setExperience($draftExperience)
            ->setStartAt(new \DateTimeImmutable('+20 days 09:00'))
            ->setEndAt(new \DateTimeImmutable('+20 days 12:00'))
            ->setCapacity(12)
            ->setIsActive(true);

        $pendingBooking = (new Booking())
            ->setUser($traveler)
            ->setSlot($futureSlot)
            ->setStatus(BookingStatus::PENDING)
            ->setSeats(2)
            ->setTotalPrice('130.00');
        $futureSlot->setRemainingPlaces(8);

        $paidBooking = (new Booking())
            ->setUser($traveler)
            ->setSlot($pastSlot)
            ->setStatus(BookingStatus::PAID)
            ->setSeats(2)
            ->setTotalPrice('130.00');

        $successfulPayment = (new Payment())
            ->setBooking($paidBooking)
            ->setAmount('130.00')
            ->setStatus(PaymentStatus::SUCCESS)
            ->setProvider('mock')
            ->setTransactionRef('mock_seed_success_001');

        $review = (new Review())
            ->setUser($traveler)
            ->setExperience($publishedExperience)
            ->setRating(5)
            ->setComment('Une expérience chaleureuse, bien rythmée et très accessible pour débuter en céramique.');

        foreach ([
            $admin,
            $organizer,
            $traveler,
            $publishedExperience,
            $draftExperience,
            $archivedExperience,
            $futureSlot,
            $secondFutureSlot,
            $pastSlot,
            $draftSlot,
            $pendingBooking,
            $paidBooking,
            $successfulPayment,
            $review,
        ] as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
