<?php

namespace App\Dto\OrganizerRequest;

use Symfony\Component\Validator\Constraints as Assert;

class CreateOrganizerRequestInput
{
    #[Assert\NotBlank(message: 'La motivation est requise.')]
    #[Assert\Length(min: 20, minMessage: 'La motivation doit contenir au moins {{ limit }} caracteres.')]
    public string $motivation = '';
}
