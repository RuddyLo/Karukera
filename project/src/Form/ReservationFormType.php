<?php

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            ->add('acceptTerms', CheckboxType::class, [
                'label' => 'J\'accepte les Conditions Générales de Vente',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-check-input',
                ]
            ])
            ->add('acceptPrivacy', CheckboxType::class, [
                'label' => 'J\'accepte la Politique de Confidentialité',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-check-input',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
