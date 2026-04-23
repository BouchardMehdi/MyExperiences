<?php

namespace App\Entity;

use App\Enum\OrganizerRequestStatus;
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

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La motivation est requise.')]
    #[Assert\Length(min: 20, minMessage: 'La motivation doit contenir au moins {{ limit }} caracteres.')]
    private ?string $motivation = null;

    #[ORM\Column(length: 20, enumType: OrganizerRequestStatus::class)]
    private OrganizerRequestStatus $status = OrganizerRequestStatus::PENDING;

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

    public function isPending(): bool
    {
        return OrganizerRequestStatus::PENDING === $this->status;
    }
}
