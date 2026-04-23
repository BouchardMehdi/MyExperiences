<?php

namespace App\Entity;

use App\Enum\ExperienceStatus;
use App\Repository\ExperienceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExperienceRepository::class)]
#[ORM\Table(name: 'experience')]
#[ORM\Index(name: 'idx_experience_organizer', columns: ['organizer_id'])]
#[ORM\Index(name: 'idx_experience_public_filters', columns: ['status', 'location', 'price'])]
class Experience
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est requis.')]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est requise.')]
    #[Assert\Length(min: 20, minMessage: 'La description doit contenir au moins {{ limit }} caracteres.')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix est requis.')]
    #[Assert\PositiveOrZero(message: 'Le prix doit etre positif ou nul.')]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le lieu est requis.')]
    #[Assert\Length(max: 255)]
    private ?string $location = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La duree est requise.')]
    #[Assert\Positive(message: 'La duree doit etre superieure a 0 minute.')]
    private ?int $duration = null;

    #[ORM\Column(length: 20, enumType: ExperienceStatus::class)]
    private ExperienceStatus $status = ExperienceStatus::DRAFT;

    #[ORM\ManyToOne(inversedBy: 'experiences')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $organizer = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Slot>
     */
    #[ORM\OneToMany(mappedBy: 'experience', targetEntity: Slot::class, orphanRemoval: true)]
    #[ORM\OrderBy(['startAt' => 'ASC'])]
    private Collection $slots;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(mappedBy: 'experience', targetEntity: Review::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $reviews;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->slots = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim($title);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = trim($description);

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = trim($location);

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getStatus(): ExperienceStatus
    {
        return $this->status;
    }

    public function setStatus(ExperienceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPublished(): bool
    {
        return ExperienceStatus::PUBLISHED === $this->status;
    }

    /**
     * @return Collection<int, Slot>
     */
    public function getSlots(): Collection
    {
        return $this->slots;
    }

    public function addSlot(Slot $slot): static
    {
        if (!$this->slots->contains($slot)) {
            $this->slots->add($slot);
            $slot->setExperience($this);
        }

        return $this;
    }

    public function removeSlot(Slot $slot): static
    {
        if ($this->slots->removeElement($slot) && $slot->getExperience() === $this) {
            $slot->setExperience(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setExperience($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review) && $review->getExperience() === $this) {
            $review->setExperience(null);
        }

        return $this;
    }

    public function getAverageRating(): ?float
    {
        if ($this->reviews->isEmpty()) {
            return null;
        }

        $sum = array_reduce(
            $this->reviews->toArray(),
            static fn (int $carry, Review $review): int => $carry + $review->getRating(),
            0
        );

        return round($sum / $this->reviews->count(), 1);
    }
}
