<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

class LoginInput
{
    #[Assert\NotBlank(message: "L'email est requis.")]
    #[Assert\Email(message: 'Veuillez saisir un email valide.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Le mot de passe est requis.')]
    public string $password = '';
}
