<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'review')]
#[ORM\UniqueConstraint(name: 'uniq_review_user_experience', columns: ['user_id', 'experience_id'])]
#[ORM\Index(name: 'idx_review_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_review_experience', columns: ['experience_id'])]
#[ORM\Index(name: 'idx_review_experience_created_at', columns: ['experience_id', 'created_at'])]
#[UniqueEntity(fields: ['user', 'experience'], message: 'Vous avez deja laisse un avis pour cette experience.')]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Experience $experience = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La note est requise.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit etre comprise entre {{ min }} et {{ max }}.')]
    private int $rating = 5;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le commentaire est requis.')]
    #[Assert\Length(min: 10, minMessage: 'Le commentaire doit contenir au moins {{ limit }} caracteres.')]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

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

    public function getExperience(): ?Experience
    {
        return $this->experience;
    }

    public function setExperience(?Experience $experience): static
    {
        $this->experience = $experience;

        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = trim($comment);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
