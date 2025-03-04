<?php

namespace App\Form;

use App\Entity\CandidatureOffre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Validator\Constraints\File;

class CandidatureOffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'En attente',
                    'Sélectionnée' => 'Sélectionnée',
                    'Rejetée' => 'Rejetée',
                ],
            ])
            ->add('cvFile', VichFileType::class, [
                'label' => 'CV (PDF file)',
                'required' => true,
                'allow_delete' => true,
                'download_uri' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
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