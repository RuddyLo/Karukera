<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => "Appartement",
                'required' => false,
                'attr' => [
                    'placeholder' => "Nom de l'appartement",
                    'class' => 'form-control'
                ],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Départ',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Arrivée',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ]
            ]);
    }
}
