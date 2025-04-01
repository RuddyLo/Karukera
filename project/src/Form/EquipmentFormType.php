<?php

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\Equipment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipmentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => "Nom de l'equipement",
                'row_attr' => [
                    'class' => 'form-group col-sm-6 m-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],

            ])
            ->add('iconUrl', FileType::class, [
                'mapped' => false,
                'label' => false,
                'required' => false,
                'row_attr' => [
                    'class' => 'form-group col-12',
                ],
                'attr'      => [
                    'accept' => 'image/*',
                    'class' => 'd-none',

                ]
            ])
           
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}
