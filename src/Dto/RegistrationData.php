<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationData
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.')]
    public string $prenom = '';

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    public string $nom = '';

    #[Assert\NotBlank(message: 'L’adresse e-mail est obligatoire.', normalizer: 'trim')]
    #[Assert\Email(message: 'Veuillez saisir une adresse e-mail valide.')]
    #[Assert\Length(max: 180, maxMessage: 'L’adresse e-mail ne peut pas dépasser {{ limit }} caractères.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\Length(
        min: 12,
        max: 4096,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Length(max: 4096, countUnit: Assert\Length::COUNT_BYTES, maxMessage: 'Le mot de passe est trop volumineux (4 096 octets maximum).')]
    public string $plainPassword = '';

    public string $telephone = '';

    public string $specialite = '';

    public string $presentation = '';

    public string $etablissementNom = '';

    public string $etablissementAdresse = '';

    public string $etablissementVille = '';

    public string $etablissementSite = '';
}
