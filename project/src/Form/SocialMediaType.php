<?php

namespace App\Form;

use App\Entity\SocialMedia;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocialMediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('platform', ChoiceType::class, [
                'label' => 'Plateforme',
                'choices' => $options['platform_choices'],
                'row_attr' => [
                    'class' => 'form-group col-sm-6 m-2',
                ],
                'attr' => [
                    'class' => 'form-select',
                    'required' => true,
                ],
                'placeholder' => 'Choisir une plateforme',
            ])
            ->add('url', TextType::class, [
                'label' => 'URL',
                'row_attr' => [
                    'class' => 'form-group col-sm-6 m-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'required' => true,
                ],
            ])
            ->add('icon', ChoiceType::class, [
                'label' => 'Icône Bootstrap',
                'choices' => [
                    'Facebook' => 'bi bi-facebook',
                    'Instagram' => 'bi bi-instagram',
                    'Twitter' => 'bi bi-twitter',
                    'TikTok' => 'bi bi-tiktok',
                    'YouTube' => 'bi bi-youtube',
                    'LinkedIn' => 'bi bi-linkedin',
                ],
                'row_attr' => [
                    'class' => 'form-group col-sm-6 m-2',
                ],
                'attr' => [
                    'class' => 'form-select',
                    'required' => true,
                ],
                'placeholder' => 'Choisir une icône',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SocialMedia::class,
            'platform_choices' => [
                'Facebook' => 'Facebook',
                'Instagram' => 'Instagram',
                'Twitter' => 'Twitter',
                'TikTok' => 'TikTok',
                'YouTube' => 'YouTube',
                'LinkedIn' => 'LinkedIn',
            ],
            'choice_translation_domain' => false,
        ]);
    }
}