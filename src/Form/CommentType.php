<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Add your comment',
                'attr' => ['rows' => 3, 'placeholder' => 'Write a comment...'],
                'required' => false,
            ])
            ->add('gifUrl', HiddenType::class, [
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Submit Comment',
                'attr' => ['class' => 'btn btn-secondary mt-1'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'csrf_protection' => false, // Disable CSRF so the manually built form can submit properly
        ]);
    }
}
