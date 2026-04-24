<?php

namespace App\Dto\OrganizerRequest;

use App\Enum\OrganizerBusinessType;
use App\Enum\OrganizerEventType;
use Symfony\Component\Validator\Constraints as Assert;

class CreateOrganizerRequestInput
{
    #[Assert\NotBlank(message: 'Le nom de structure ou de profil public est requis.')]
    #[Assert\Length(min: 2, max: 150)]
    public string $organizationName = '';

    #[Assert\NotBlank(message: 'Le numero de telephone est requis.')]
    #[Assert\Regex(
        pattern: '/^\+?[0-9\s().-]{8,20}$/',
        message: 'Veuillez saisir un numero de telephone valide.'
    )]
    public string $phoneNumber = '';

    #[Assert\NotBlank(message: "L'adresse est requise.")]
    #[Assert\Length(min: 5, max: 255)]
    public string $streetAddress = '';

    #[Assert\NotBlank(message: 'Le code postal est requis.')]
    #[Assert\Length(min: 3, max: 20)]
    public string $postalCode = '';

    #[Assert\NotBlank(message: 'La ville est requise.')]
    #[Assert\Length(min: 2, max: 120)]
    public string $city = '';

    #[Assert\NotBlank(message: 'Le pays est requis.')]
    #[Assert\Length(min: 2, max: 120)]
    public string $country = 'France';

    #[Assert\NotBlank(message: 'Le type de structure est requis.')]
    #[Assert\Choice(callback: [OrganizerBusinessType::class, 'values'], message: 'Le type de structure est invalide.')]
    public string $businessType = '';

    /**
     * @var list<string>
     */
    #[Assert\Count(min: 1, max: 6, minMessage: 'Selectionnez au moins un type d evenement.')]
    #[Assert\Choice(
        callback: [OrganizerEventType::class, 'values'],
        multiple: true,
        message: "Un ou plusieurs types d'evenements sont invalides."
    )]
    public array $eventTypes = [];

    #[Assert\NotBlank(message: "La description de l'activite est requise.")]
    #[Assert\Length(min: 60, minMessage: "La description de l'activite doit contenir au moins {{ limit }} caracteres.")]
    public string $activityDescription = '';

    #[Assert\Length(max: 255)]
    #[Assert\Url(message: 'Le site web doit etre une URL valide.')]
    public string $websiteUrl = '';

    #[Assert\Length(max: 500)]
    public string $socialLinks = '';

    #[Assert\NotBlank(message: 'Le SIRET est requis.')]
    #[Assert\Length(max: 14)]
    #[Assert\Regex(pattern: '/^\d{14}$/', message: 'Le SIRET doit contenir exactement 14 chiffres.')]
    public string $siret = '';

    #[Assert\NotBlank(message: 'La motivation est requise.')]
    #[Assert\Length(min: 20, minMessage: 'La motivation doit contenir au moins {{ limit }} caracteres.')]
    public string $motivation = '';
}
