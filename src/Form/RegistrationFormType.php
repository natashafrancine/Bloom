<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('name', TextType::class, [
        'label' => 'Full Name',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'Enter your full name'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Name is required']),
          new Assert\Length([
            'min' => 2,
            'minMessage' => 'Name must be at least 2 characters'
          ])
        ]
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email Address',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'Enter your email address'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Email is required']),
          new Assert\Email(['message' => 'Please enter a valid email address'])
        ]
      ])
      ->add('plainPassword', PasswordType::class, [
        'label' => 'Password',
        'mapped' => false,
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'Create a password (min. 6 characters)',
          'autocomplete' => 'new-password'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Password is required']),
          new Assert\Length([
            'min' => 6,
            'minMessage' => 'Password must be at least 6 characters long'
          ])
        ]
      ])
      ->add('confirmPassword', PasswordType::class, [
        'label' => 'Confirm Password',
        'mapped' => false,
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'Confirm your password',
          'autocomplete' => 'new-password'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Please confirm your password'])
        ]
      ])
      ->add('agreeTerms', CheckboxType::class, [
        'label' => 'I agree to the Terms & Conditions',
        'mapped' => false,
        'attr' => [
          'class' => 'form-check-input'
        ],
        'constraints' => [
          new Assert\IsTrue(['message' => 'You must agree to the terms'])
        ]
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => User::class,
    ]);
  }
}
