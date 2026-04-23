<?php

namespace App\Dto\Payment;

use Symfony\Component\Validator\Constraints as Assert;

class ProcessPaymentInput
{
    #[Assert\NotBlank(message: 'Le resultat du paiement est requis.')]
    #[Assert\Choice(
        choices: ['success', 'failure'],
        message: 'Le resultat du paiement doit etre "success" ou "failure".'
    )]
    public string $outcome = 'success';
}
