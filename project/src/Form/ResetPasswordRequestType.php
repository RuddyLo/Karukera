<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'reset.form.email',
                'attr'  => ['class' => 'form-control form-control-lg'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'reset.form.submit_request',
                'attr'  => ['class' => 'btn btn-info btn-lg w-100 text-white mt-3'],
            ]);
    }
}