<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control p_input',
                    'placeholder' => 'Entrez votre email'
                ]
            ])
            ->add('nom', TextType::class, [
                'attr' => [
                    'class' => 'form-control p_input',
                    'placeholder' => 'Entrez votre nom',
                    'novalidate' => true
                ]
            ])
            ->add('prenom', TextType::class, [
                'attr' => [
                    'class' => 'form-control p_input',
                    'placeholder' => 'Entrez votre prénom',
                    'novalidate' => true
                ]
            ])
            ->add('age', IntegerType::class, [
                'attr' => [
                    'class' => 'form-control p_input',
                    'placeholder' => 'Entrez votre âge',
                    'novalidate' => true
                ]
            ])
            ->add('numtlf', TelType::class, [
                'attr' => [
                    'class' => 'form-control p_input',
                    'placeholder' => 'Entrez votre numéro de téléphone',
                    'novalidate' => true
                ]
            ])
            /*->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])*/
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control p_input',
                    'novalidate' => true
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
                
            ])

            ->add('avatarUrl', HiddenType::class, [
                'required' => false,
            ])

            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'expanded' => true, // Pour afficher des boutons radio
                'multiple' => true,
                'attr' => [
                    'class' => 'role-choices'
                ]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'attr' => [
                'novalidate' => true // Désactive la validation HTML5 pour tout le formulaire
            ]
        ]);
    }
}