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
        if ($options['show_apartment_field']) {
            $builder->add('apartment', EntityType::class, [
                'class' => Apartment::class,
                'choice_label' => 'name',
                'label' => "Appartement",
                'placeholder' => "Choisir un appartement",
                'query_builder' => fn (\Doctrine\ORM\EntityRepository $er) => $er->createQueryBuilder('a')
                    ->where('a.is_deleted = false')
                    ->orderBy('a.name', 'ASC'),
                'row_attr' => [
                    'class' => 'form-group col-sm-6 my-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
        }

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
            'show_apartment_field' => true,
        ]);
    }
}
