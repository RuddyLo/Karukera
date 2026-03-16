<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           ->add('email', EmailType::class, [
                'label' => 'register.form.email',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'register.validation.password_required']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'register.validation.password_min',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[A-Z]/',
                        'message' => 'register.validation.password_uppercase',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[a-z]/',
                        'message' => 'register.validation.password_lowercase',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/\d/',
                        'message' => 'register.validation.password_digit',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/[\W]/',
                        'message' => 'register.validation.password_special',
                    ]),
                ],
                'first_options' => ['label' => 'register.form.password', 'attr' => ['class' => 'form-control']],
                'second_options' => ['label' => 'register.form.password_confirm', 'attr' => ['class' => 'form-control']],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'register.submit',
                'attr' => ['class' => 'btn btn-primary mt-3 px-4']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
