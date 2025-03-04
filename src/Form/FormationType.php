<?php

namespace App\Form;

use App\Entity\Formation;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la formation',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le titre ne peut pas être vide'
                    ]),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 100,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Entrez le titre de la formation'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description ne peut pas être vide'
                    ]),
                    new Assert\Length([
                        'min' => 20,
                        'max' => 500,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'rows' => 4,
                    'placeholder' => 'Décrivez le contenu de la formation...'
                ]
            ])
            ->add('date_debut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date de début est requise'
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de début doit être ultérieure ou égale à aujourd\'hui'
                    ])
                ],
                'attr' => ['class' => 'form-control form-control-lg']
            ])
            ->add('date_fin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date de fin est requise'
                    ]),
                    new Assert\GreaterThan([
                        'propertyPath' => 'parent.all[date_debut].data',
                        'message' => 'La date de fin doit être ultérieure à la date de début'
                    ])
                ],
                'attr' => ['class' => 'form-control form-control-lg']
            ])
            ->add('nbrpart', NumberType::class, [
                'label' => 'Nombre de participants',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nombre de participants est requis'
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 100,
                        'notInRangeMessage' => 'Le nombre de participants doit être entre {{ min }} et {{ max }}',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'min' => 1,
                    'max' => 100
                ]
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'TND',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le prix est requis'
                    ]),
                    new Assert\PositiveOrZero([
                        'message' => 'Le prix ne peut pas être négatif'
                    ])
                ],
                'attr' => ['class' => 'form-control form-control-lg']
            ])
            ->add('video', UrlType::class, [
                'label' => 'Lien vidéo',
                'required' => false,
                'constraints' => [
                    new Assert\Url([
                        'message' => 'Veuillez entrer une URL valide'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^https?:\/\/(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)([\w-]{11})/',
                        'message' => 'Veuillez entrer une URL YouTube valide (ex: https://youtube.com/watch?v=... ou https://youtu.be/...)'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'URL de la vidéo YouTube (ex: https://youtube.com/watch?v=... ou https://youtu.be/...)'
                ]
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'label' => 'Catégorie',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner une catégorie'
                    ])
                ],
                'attr' => ['class' => 'form-control form-control-lg']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Créer la formation',
                'attr' => ['class' => 'btn btn-gradient-primary btn-lg font-weight-medium']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
} 