<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ResendVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Adresse e-mail',
            'mapped' => true,
            'trim' => true,
            'attr' => [
                'maxlength' => 180,
                'autocomplete' => 'email',
            ],
            'constraints' => [
                new Assert\NotBlank(message: 'L’adresse e-mail est obligatoire.', normalizer: 'trim'),
                new Assert\Email(message: 'Veuillez saisir une adresse e-mail valide.'),
                new Assert\Length(
                    max: 180,
                    maxMessage: 'L’adresse e-mail ne peut pas dépasser {{ limit }} caractères.'
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
            'csrf_token_id' => 'resend_verification',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'resend_verification';
    }
}
