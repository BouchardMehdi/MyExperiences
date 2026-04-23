<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterInput
{
    #[Assert\NotBlank(message: "L'email est requis.")]
    #[Assert\Email(message: 'Veuillez saisir un email valide.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Le mot de passe est requis.')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caracteres.'
    )]
    public string $password = '';

    #[Assert\NotBlank(message: 'Le prenom est requis.')]
    #[Assert\Length(max: 100)]
    public string $firstname = '';

    #[Assert\NotBlank(message: 'Le nom est requis.')]
    #[Assert\Length(max: 100)]
    public string $lastname = '';
}
