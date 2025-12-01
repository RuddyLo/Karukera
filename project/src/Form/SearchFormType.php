<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2 mt-2',
                    'placeholder' => 'Choisissez une date de début',
                    'readonly' => true,
                ]
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2 mt-2',
                    'placeholder' => 'Choisissez une date de fin',
                    'readonly' => true,
                ]
            ])

        ;
    }
}
