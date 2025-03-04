<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class ArticleFrontType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre ne peut pas être vide.']),
                    new Assert\Regex([
                        'pattern' => '/^\D+$/',
                        'message' => 'Le titre ne peut pas être uniquement numérique.',
                    ]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description ne peut pas être vide.']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins 10 caractères.',
                    ]),
                ],
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('prix', NumberType::class, [
                'constraints' => [
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'Le prix doit être supérieur à 0.',
                    ]),
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('contenu', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG ou GIF)'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('nbrarticle', IntegerType::class, [
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Le nombre d\'articles doit être au moins 1.',
                    ]),
                ],
            ])
            ->add('categorie', ChoiceType::class, [
                'choices' => [
                    'Peinture' => 'Peinture',
                    'Sculpture' => 'Sculpture',
                    'Photographie' => 'Photographie',
                    'DessinEtIllustration' => 'DessinEtIllustration',
                    'ArtNumerique' => 'ArtNumerique',
                    'Architecture' => 'Architecture',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez choisir une catégorie.'])
                ],
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
} 