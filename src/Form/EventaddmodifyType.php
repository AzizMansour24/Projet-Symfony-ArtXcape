<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class EventaddmodifyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le titre est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
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
            ->add('dateEvenement', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => true,
                'empty_data' => null,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date est obligatoire'
                    ]),
                    new Assert\Type([
                        'type' => \DateTimeInterface::class,
                        'message' => 'La date n\'est pas valide'
                    ]),
                    new Assert\GreaterThan([
                        'value' => 'today',
                        'message' => 'La date doit être ultérieure à aujourd\'hui'
                    ])
                ]
            ])
            ->add('lieu', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le lieu est obligatoire'
                    ])
                ]
            ])
            ->add('nombreBillets', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nombre de billets est obligatoire'
                    ]),
                    new Assert\Positive([
                        'message' => 'Le nombre de billets doit être positif'
                    ])
                ]
            ])
            ->add('image', FileType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG ou PNG)'
                    ])
                ]
            ])
            ->add('timestart', TimeType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'heure de début est obligatoire'
                    ])
                ]
            ])
            ->add('eventMission', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La mission de l\'événement est obligatoire'
                    ])
                ]
            ])
            ->add('donation_objective', NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'objectif de don est obligatoire'
                    ]),
                    new Assert\Positive([
                        'message' => 'L\'objectif de don doit être positif'
                    ])
                ]
            ])
            ->add('seatprice', NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le prix du siège est obligatoire'
                    ]),
                    new Assert\Positive([
                        'message' => 'Le prix du siège doit être positif'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
