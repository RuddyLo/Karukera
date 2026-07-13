<?php

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\Equipment;
use App\Entity\PricePeriod;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ApartmentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $yes_or_not = [
            'Non' => false,
            'Oui' => true
        ];
        $builder
            ->add('pricePeriod', PricePeriodType::class, [
                    'label' => 'Nouvelle période de prix (optionnel)',

                    'mapped' => false, // Important : non mappé à l'entité principale
                    'required' => false,
                    'show_apartment_field' => false,
                ])
            ->add('name', TextType::class, [
                'label' => "Dénomination de l'appartement",
                'row_attr' => [
                    'class' => 'form-group col-sm-6 my-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],

            ])
            ->add('price', TextType::class, [
                'label' => "Prix",
                'row_attr' => [
                    'class' => 'form-group col-sm-6 my-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],

            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'row_attr' => [
                    'class' => 'form-group col-12 m-2'
                ],
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'required' => true,
                ],

            ])
            ->add('is_active', ChoiceType::class, [
                'label' => 'Afficher',
                'row_attr' => [
                    'class' => 'form-group col-md-6 my-2'
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'choices' => $yes_or_not
            ])
            ->add('is_favorite', ChoiceType::class, [
                'label' => 'Mettre dans à la une',
                'row_attr' => [
                    'class' => 'form-group col-md-6 my-2'
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'choices' => $yes_or_not
            ])
            ->add('imageUrl', FileType::class, [
                'mapped' => false,
                'label' => false,
                'required' => false,
                'row_attr' => [
                    'class' => 'form-group col-12',
                ],
                'attr'      => [
                    'accept' => 'image/*',
                    'class' => 'd-none',

                ],
                'constraints' => [
                    new File(maxSize: '10M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']),
                ],
            ])
            ->add('equipments', EntityType::class, [
                'class' => Equipment::class, 
                'choice_label' => 'name', 
                'multiple' => true, 
                'expanded' => false, 
                'row_attr' => ['class' => 'form-group'],
                'attr' => ['class' => 'dropdown-checkbox-select form-select'],
                'label' => 'Équipements',
            ])
            ->add('locale', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Langue de saisie',
                'choices' => [
                    'Français' => 'fr',
                    'English'  => 'en',
                ],
                'data' => $options['locale'], // présélectionne la locale courante
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'form-group col-sm-6 my-2'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Apartment::class,
            'locale'     => 'fr',
        ]);
    }
}
