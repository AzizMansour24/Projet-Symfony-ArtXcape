<?php

namespace App\Form;

use App\Entity\CandidatureOffre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class CandidatureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('competences', TextareaType::class, [
                'label' => 'Compétences',
                'required' => true,
            ])
            ->add('lettreMotivation', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'required' => true,
            ])
            ->add('cvFile', FileType::class, [
                'label' => 'CV (PDF file)',
                'mapped' => true,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '24M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidatureOffre::class,
        ]);
    }
}