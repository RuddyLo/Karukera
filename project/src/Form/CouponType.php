<?php

namespace App\Form;

use App\Entity\Apartment;
use App\Entity\Coupon;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CouponType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'row_attr' => ['class' => 'form-group col-sm-6 my-2'],
                'attr' => ['class' => 'form-control text-uppercase'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de réduction',
                'choices' => [
                    'Pourcentage (%)' => Coupon::TYPE_PERCENTAGE,
                    'Montant fixe (€)' => Coupon::TYPE_FIXED,
                ],
                'row_attr' => ['class' => 'form-group col-sm-6 my-2'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('value', NumberType::class, [
                'label' => 'Valeur',
                'row_attr' => ['class' => 'form-group col-sm-6 my-2'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'row_attr' => ['class' => 'form-group col-sm-6 my-2 d-flex align-items-end'],
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('validFrom', null, [
                'label' => 'Valable à partir du',
                'widget' => 'single_text',
                'required' => false,
                'row_attr' => ['class' => 'form-group col-sm-6 my-2'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('validUntil', null, [
                'label' => "Valable jusqu'au",
                'widget' => 'single_text',
                'required' => false,
                'row_attr' => ['class' => 'form-group col-sm-6 my-2'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('maxUses', IntegerType::class, [
                'label' => "Nombre d'utilisations max (global)",
                'required' => false,
                'row_attr' => ['class' => 'form-group col-sm-6 my-2'],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Illimité'],
            ])
            ->add('maxUsesPerUser', IntegerType::class, [
                'label' => 'Utilisations max par client',
                'required' => false,
                'row_attr' => ['class' => 'form-group col-sm-6 my-2'],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Illimité'],
            ])
            ->add('apartments', EntityType::class, [
                'class' => Apartment::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Appartements concernés',
                'help' => 'Aucune sélection = valable pour tous les appartements',
                'query_builder' => fn (\Doctrine\ORM\EntityRepository $er) => $er->createQueryBuilder('a')
                    ->where('a.is_deleted = false')
                    ->orderBy('a.name', 'ASC'),
                'row_attr' => ['class' => 'form-group col-sm-12 my-2'],
                'attr' => ['class' => 'dropdown-checkbox-select form-select'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Coupon::class,
        ]);
    }
}
