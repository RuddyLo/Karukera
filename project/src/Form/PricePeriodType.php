<?php

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\PricePeriod;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PricePeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('price', NumberType::class, [
                'label' => "Prix Pour la période",
                'row_attr' => [
                    'class' => 'form-group col-sm-6 my-2',
                ],
                'attr' => [
                    'type'=> 'number',
                    'class' => 'form-control',
                    'required' => false,
                ],

            ])
            ->add('startDate', null, [
                'label' => "Début période",
                'widget' => 'single_text',
                'row_attr' => [
                    'class' => 'form-group col-sm-6 my-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => false,
                    'disabled'
                ],
            ])
            
            ->add('endDate', null, [
                'label' => "Fin période",
                'widget' => 'single_text',
                'row_attr' => [
                    'class' => 'form-group col-sm-6 my-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => false,
                ],
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PricePeriod::class,
        ]);
    }
}
