<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 🌸 Product name
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => [
                    'placeholder' => 'Enter product name',
                    'class' => 'form-control',
                ],
            ])

            // 🌸 Description
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Enter product description',
                    'rows' => 4,
                    'class' => 'form-control',
                ],
            ])

            // 🌸 Price
            ->add('price', NumberType::class, [
                'label' => 'Price (₱)',
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control',
                ],
                'scale' => 2,
            ])

            // 🌸 Stock
            ->add('stock', NumberType::class, [
                'label' => 'Stock Quantity',
                'attr' => [
                    'placeholder' => 'e.g., 50',
                    'class' => 'form-control',
                ],
            ])

            // 🌸 Category dropdown
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
                'label' => 'Category',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            // 🌸 Shop checkbox (add product to shop)
            ->add('shop', CheckboxType::class, [
                'label'    => 'Add to Shop',
                'required' => false,
                'mapped'   => false, // handled manually in controller
                'attr'     => [
                    'class' => 'form-check-input',
                ],
            ])

            // 🌸 Product image upload
            ->add('image', FileType::class, [
                'label' => 'Product Image (JPG, PNG, GIF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPG, PNG, or GIF)',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'onchange' => 'previewImage(event)',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
