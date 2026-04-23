<?php

namespace App\Dto\Review;

use Symfony\Component\Validator\Constraints as Assert;

class CreateReviewInput
{
    #[Assert\NotBlank(message: 'La note est requise.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit etre comprise entre {{ min }} et {{ max }}.')]
    public int $rating = 5;

    #[Assert\NotBlank(message: 'Le commentaire est requis.')]
    #[Assert\Length(min: 10, minMessage: 'Le commentaire doit contenir au moins {{ limit }} caracteres.')]
    public string $comment = '';
}
