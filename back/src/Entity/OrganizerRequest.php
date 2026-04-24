<?php

namespace App\Entity;

use App\Enum\OrganizerRequestStatus;
use App\Enum\OrganizerRequestScreeningStatus;
use App\Repository\OrganizerRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizerRequestRepository::class)]
#[ORM\Table(name: 'organizer_request')]
#[ORM\Index(name: 'idx_organizer_request_user_status_created_at', columns: ['user_id', 'status', 'created_at'])]
#[ORM\Index(name: 'idx_organizer_request_status_created_at', columns: ['status', 'created_at'])]
class OrganizerRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'organizerRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'reviewedOrganizerRequests')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $reviewedBy = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le nom de structure ou de profil public est requis.')]
    #[Assert\Length(min: 2, max: 150)]
    private ?string $organizationName = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le numero de telephone est requis.')]
    #[Assert\Regex(
        pattern: '/^\+?[0-9\s().-]{8,20}$/',
        message: 'Veuillez saisir un numero de telephone valide.'
    )]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse est requise.")]
    #[Assert\Length(min: 5, max: 255)]
    private ?string $streetAddress = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le code postal est requis.')]
    #[Assert\Length(min: 3, max: 20)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'La ville est requise.')]
    #[Assert\Length(min: 2, max: 120)]
    private ?string $city = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Le pays est requis.')]
    #[Assert\Length(min: 2, max: 120)]
    private ?string $country = 'France';

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le type de structure est requis.')]
    private ?string $businessType = null;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $eventTypes = [];

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description de l'activite est requise.")]
    #[Assert\Length(min: 60, minMessage: "La description de l'activite doit contenir au moins {{ limit }} caracteres.")]
    private ?string $activityDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $websiteUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $socialLinks = null;

    #[ORM\Column(length: 14)]
    #[Assert\NotBlank(message: 'Le SIRET est requis.')]
    #[Assert\Regex(pattern: '/^\d{14}$/', message: 'Le SIRET doit contenir exactement 14 chiffres.')]
    private ?string $siret = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La motivation est requise.')]
    #[Assert\Length(min: 20, minMessage: 'La motivation doit contenir au moins {{ limit }} caracteres.')]
    private ?string $motivation = null;

    #[ORM\Column(length: 20, enumType: OrganizerRequestStatus::class)]
    private OrganizerRequestStatus $status = OrganizerRequestStatus::PENDING;

    #[ORM\Column(length: 20, enumType: OrganizerRequestScreeningStatus::class)]
    private OrganizerRequestScreeningStatus $screeningStatus = OrganizerRequestScreeningStatus::NEEDS_REVIEW;

    /**
     * @var array<string, array<string, mixed>>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $screeningChecks = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;

        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(string $organizationName): static
    {
        $this->organizationName = trim($organizationName);

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = trim($phoneNumber);

        return $this;
    }

    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    public function setStreetAddress(string $streetAddress): static
    {
        $this->streetAddress = trim($streetAddress);

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = trim($postalCode);

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = trim($city);

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = trim($country);

        return $this;
    }

    public function getBusinessType(): ?string
    {
        return $this->businessType;
    }

    public function setBusinessType(string $businessType): static
    {
        $this->businessType = strtoupper(trim($businessType));

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getEventTypes(): array
    {
        return $this->eventTypes;
    }

    /**
     * @param list<string> $eventTypes
     */
    public function setEventTypes(array $eventTypes): static
    {
        $normalized = [];

        foreach ($eventTypes as $eventType) {
            if (!is_string($eventType)) {
                continue;
            }

            $value = strtoupper(trim($eventType));
            if ('' !== $value) {
                $normalized[] = $value;
            }
        }

        $this->eventTypes = array_values(array_unique($normalized));

        return $this;
    }

    public function getActivityDescription(): ?string
    {
        return $this->activityDescription;
    }

    public function setActivityDescription(string $activityDescription): static
    {
        $this->activityDescription = trim($activityDescription);

        return $this;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): static
    {
        $this->websiteUrl = null === $websiteUrl ? null : trim($websiteUrl);

        if ('' === $this->websiteUrl) {
            $this->websiteUrl = null;
        }

        return $this;
    }

    public function getSocialLinks(): ?string
    {
        return $this->socialLinks;
    }

    public function setSocialLinks(?string $socialLinks): static
    {
        $this->socialLinks = null === $socialLinks ? null : trim($socialLinks);

        if ('' === $this->socialLinks) {
            $this->socialLinks = null;
        }

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = trim($siret);

        return $this;
    }

    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(string $motivation): static
    {
        $this->motivation = trim($motivation);

        return $this;
    }

    public function getStatus(): OrganizerRequestStatus
    {
        return $this->status;
    }

    public function setStatus(OrganizerRequestStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getScreeningStatus(): OrganizerRequestScreeningStatus
    {
        return $this->screeningStatus;
    }

    public function setScreeningStatus(OrganizerRequestScreeningStatus $screeningStatus): static
    {
        $this->screeningStatus = $screeningStatus;

        return $this;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getScreeningChecks(): array
    {
        return $this->screeningChecks;
    }

    /**
     * @param array<string, array<string, mixed>> $screeningChecks
     */
    public function setScreeningChecks(array $screeningChecks): static
    {
        $normalizedChecks = [];

        foreach ($screeningChecks as $code => $check) {
            if (!is_string($code) || !is_array($check)) {
                continue;
            }

            $normalizedChecks[$code] = $check;
        }

        $this->screeningChecks = $normalizedChecks;

        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function approve(User $admin): static
    {
        $this->status = OrganizerRequestStatus::APPROVED;
        $this->reviewedBy = $admin;
        $this->processedAt = new \DateTimeImmutable();

        return $this;
    }

    public function reject(User $admin): static
    {
        $this->status = OrganizerRequestStatus::REJECTED;
        $this->reviewedBy = $admin;
        $this->processedAt = new \DateTimeImmutable();

        return $this;
    }

    public function rejectAutomatically(): static
    {
        $this->status = OrganizerRequestStatus::REJECTED;
        $this->reviewedBy = null;
        $this->processedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isPending(): bool
    {
        return OrganizerRequestStatus::PENDING === $this->status;
    }
}
