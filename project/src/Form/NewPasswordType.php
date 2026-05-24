<?php

namespace App\Form;

use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class NewPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type'          => PasswordType::class,
                'constraints'   => [
                    new Assert\NotBlank(['message' => 'register.validation.password_required']),
                    new Assert\Length(['min' => 8, 'minMessage' => 'register.validation.password_min']),
                    new Assert\Regex(['pattern' => '/[A-Z]/', 'message' => 'register.validation.password_uppercase']),
                    new Assert\Regex(['pattern' => '/[a-z]/', 'message' => 'register.validation.password_lowercase']),
                    new Assert\Regex(['pattern' => '/\d/', 'message' => 'register.validation.password_digit']),
                    new Assert\Regex(['pattern' => '/[\W]/', 'message' => 'register.validation.password_special']),
                ],
                'first_options'  => ['label' => 'reset.form.new_password', 'attr' => ['class' => 'form-control form-control-lg']],
                'second_options' => ['label' => 'reset.form.confirm_password', 'attr' => ['class' => 'form-control form-control-lg']],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'reset.form.submit_new',
                'attr'  => ['class' => 'btn btn-info btn-lg w-100 text-white mt-3'],
            ])
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'reset_password',
            ]);
    }
}