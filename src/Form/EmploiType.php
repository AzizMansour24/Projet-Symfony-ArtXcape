<?php

namespace App\Form;

use App\Entity\Emploi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmploiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('competences_requises', TextareaType::class, [
                'label' => 'Compétences requises',
                'required' => true,
            ])
            ->add('budget', MoneyType::class, [
                'label' => 'Budget',
                'required' => false,
                'currency' => 'DT',
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'required' => true,
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Ouvert' => 'Ouvert',
                    'En cours' => 'En cours',
                    'Fermé' => 'Fermé',
                ],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Emploi::class,
            'csrf_protection' => true,
        ]);
    }
}