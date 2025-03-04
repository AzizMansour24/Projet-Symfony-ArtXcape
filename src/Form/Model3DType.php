<?php

namespace App\Form;

use App\Entity\Model3D;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Model3DType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Model Name',
                'attr' => ['placeholder' => 'Enter model name']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['placeholder' => 'Enter model description']
            ])
            ->add('fileUrl', FileType::class, [
                'label' => '3D Model File',
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => '.glb,.gltf,.obj,.fbx']
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'titre',
                'required' => false,
                'placeholder' => 'Select an event (optional)'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Model3D::class,
        ]);
    }
} 