<?php
namespace App\Form;

use App\Entity\User;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'register.form.first_name',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'register.validation.first_name_required']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'register.validation.first_name_max',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'register.form.last_name',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'register.validation.last_name_required']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'register.validation.last_name_max',
                    ]),
                ],
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'register.form.birth_date',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'register.validation.birth_date_required']),
                    new Assert\LessThanOrEqual(value: 'today', message: 'register.validation.birth_date_future'),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'register.form.phone',
                'attr' => ['class' => 'form-control', 'placeholder' => '+33612345678'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'register.validation.phone_required']),
                    new Assert\Length([
                        'max' => 20,
                        'maxMessage' => 'register.validation.phone_max',
                    ]),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'register.form.address',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'register.validation.address_required']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'register.validation.address_max',
                    ]),
                ],
            ])
            ->add('country', CountryType::class, [
                'label' => 'register.form.country',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'register.form.country_placeholder',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'register.validation.country_required']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'register.form.email',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'register.validation.email_required']),
                    new Assert\Email(['message' => 'register.validation.email_format']),
                ],
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
            ])
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'register',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
