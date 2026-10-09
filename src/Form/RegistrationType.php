<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\RegistrationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'maxlength' => 100,
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'maxlength' => 100,
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'trim' => true,
                'attr' => [
                    'maxlength' => 180,
                    'autocomplete' => 'email',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'first_options' => [
                    'label' => 'Mot de passe',
                    'required' => true,
                    'attr' => [
                        'maxlength' => 4096,
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'required' => true,
                    'attr' => [
                        'maxlength' => 4096,
                        'autocomplete' => 'new-password',
                    ],
                ],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'maxlength' => 30,
                    'autocomplete' => 'tel',
                ],
                'constraints' => [
                    new Assert\Length(
                        max: 30,
                        maxMessage: 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères.'
                    ),
                ],
            ]);

        if ($options['profile'] === 'formateur') {
            $builder
                ->add('specialite', TextType::class, [
                    'label' => 'Spécialité',
                    'attr' => [
                        'maxlength' => 180,
                        'autocomplete' => 'organization-title',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(message: 'La spécialité est obligatoire.'),
                        new Assert\Length(
                            max: 180,
                            maxMessage: 'La spécialité ne peut pas dépasser {{ limit }} caractères.'
                        ),
                    ],
                ])
                ->add('presentation', TextareaType::class, [
                    'label' => 'Présentation',
                    'attr' => [
                        'maxlength' => 4000,
                        'autocomplete' => 'off',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(message: 'La présentation est obligatoire.'),
                        new Assert\Length(
                            max: 4000,
                            maxMessage: 'La présentation ne peut pas dépasser {{ limit }} caractères.'
                        ),
                    ],
                ]);
        }

        if ($options['profile'] === 'etablissement') {
            $builder
                ->add('etablissementNom', TextType::class, [
                    'label' => 'Nom de l’établissement',
                    'attr' => [
                        'maxlength' => 255,
                        'autocomplete' => 'organization',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(message: 'Le nom de l’établissement est obligatoire.'),
                        new Assert\Length(
                            max: 255,
                            maxMessage: 'Le nom de l’établissement ne peut pas dépasser {{ limit }} caractères.'
                        ),
                    ],
                ])
                ->add('etablissementAdresse', TextareaType::class, [
                    'label' => 'Adresse de l’établissement',
                    'attr' => [
                        'maxlength' => 1000,
                        'autocomplete' => 'street-address',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(message: 'L’adresse de l’établissement est obligatoire.'),
                        new Assert\Length(
                            max: 1000,
                            maxMessage: 'L’adresse de l’établissement ne peut pas dépasser {{ limit }} caractères.'
                        ),
                    ],
                ])
                ->add('etablissementVille', TextType::class, [
                    'label' => 'Ville',
                    'attr' => [
                        'maxlength' => 100,
                        'autocomplete' => 'address-level2',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(message: 'La ville est obligatoire.'),
                        new Assert\Length(
                            max: 100,
                            maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.'
                        ),
                    ],
                ])
                ->add('etablissementSite', UrlType::class, [
                    'label' => 'Site internet',
                    'required' => false,
                'empty_data' => '',
                    'attr' => [
                        'maxlength' => 255,
                        'autocomplete' => 'url',
                    ],
                    'constraints' => [
                        new Assert\Url(
                            protocols: ['http', 'https'],
                            message: 'Veuillez saisir une URL valide en http ou https.'
                        ),
                        new Assert\Length(
                            max: 255,
                            maxMessage: 'Le site internet ne peut pas dépasser {{ limit }} caractères.'
                        ),
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationData::class,
            'csrf_protection' => true,
        ]);
        $resolver->setRequired('profile');
        $resolver->setAllowedTypes('profile', 'string');
        $resolver->setAllowedValues('profile', ['apprenant', 'formateur', 'etablissement']);
        $resolver->setDefault(
            'csrf_token_id',
            static fn (Options $options): string => 'registration_'.$options['profile']
        );
    }

    public function getBlockPrefix(): string
    {
        return 'registration';
    }
}
