<?php

namespace App\Form;

use App\Entity\Galerie;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class GalerieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom', TextType::class, [
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Le nom est obligatoire'
                ]),
                new Assert\Regex([
                    'pattern' => '/\D/', // Vérifie qu'il y a au moins un caractère non numérique
                    'message' => 'Le nom ne peut pas contenir uniquement des chiffres'
                ])
            ]
        ])
            ->add('datecreation', DateType::class, [
                'widget' => 'single_text',
                'data' => new \DateTime(), // Définit la date du jour
                'attr' => ['class' => 'form-control', 'readonly' => true], // Empêche la modification
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Galerie::class,
        ]);
    }
}
