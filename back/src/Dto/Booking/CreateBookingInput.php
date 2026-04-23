<?php

namespace App\Dto\Booking;

use Symfony\Component\Validator\Constraints as Assert;

class CreateBookingInput
{
    #[Assert\NotBlank(message: 'Le creneau est requis.')]
    #[Assert\Positive(message: 'Le creneau est invalide.')]
    public int $slotId = 0;

    #[Assert\NotBlank(message: 'Le nombre de places est requis.')]
    #[Assert\Positive(message: 'Vous devez reserver au moins une place.')]
    public int $seats = 1;
}
