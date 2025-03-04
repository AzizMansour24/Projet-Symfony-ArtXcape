<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Galerie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ArticleType extends AbstractType
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
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description ne peut pas être vide.']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins 10 caractères.',
                    ]),
                ],
            ])
            ->add('prix', NumberType::class, [
                'constraints' => [
                    new Assert\GreaterThan([
                        'value' => 0,
                        'message' => 'Le prix doit être supérieur à 0.',
                    ]),
                ],
            ])
            ->add('date_pub', DateTimeType::class, [
                'widget' => 'single_text',
                'data' => new \DateTime(), // Définit la date courante par défaut
            ])
            ->add('disponible')
            ->add('nbrarticle', IntegerType::class, [
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Le nombre d\'articles doit être au moins 1.',
                    ]),
                ],
            ])
            ->add('contenu', FileType::class, [
                'label' => 'Image (Contenu)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
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
                'placeholder' => 'Choisissez une catégorie',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('galerie', EntityType::class, [
                'class' => Galerie::class,
                'choice_label' => 'id',
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}