<?php

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\MinimumStayPeriod;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MinimumStayPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('apartment', EntityType::class, [
                'class' => Apartment::class,
                'choice_label' => 'name',
                'label' => "Appartement",
                'query_builder' => fn (\Doctrine\ORM\EntityRepository $er) => $er->createQueryBuilder('a')
                    ->where('a.is_deleted = false')
                    ->orderBy('a.name', 'ASC'),
                'row_attr' => [
                    'class' => 'form-group col-sm-6 my-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],
            ])
            ->add('minimumDays', NumberType::class, [
                'label' => "Nombre minimum de nuits",
                'row_attr' => [
                    'class' => 'form-group col-sm-6 my-2',
                ],
                'attr' => [
                    'type'=> 'number',
                    'class' => 'form-control',
                    'min' => 1,
                    'required' => true,
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
                    'type' => 'date',
                    'required' => true,
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
                    'type' => 'date',
                    'required' => true,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MinimumStayPeriod::class,
        ]);
    }
}
