<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LoginFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('email', EmailType::class, [
        'label' => 'Email Address',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'Enter your email',
          'autocomplete' => 'email'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Email is required']),
          new Assert\Email(['message' => 'Please enter a valid email'])
        ]
      ])
      ->add('password', PasswordType::class, [
        'label' => 'Password',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'Enter your password',
          'autocomplete' => 'current-password'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Password is required'])
        ]
      ])
      ->add('_remember_me', CheckboxType::class, [
        'label' => 'Remember me',
        'required' => false,
        'attr' => [
          'class' => 'form-check-input'
        ]
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([]);
  }
}
