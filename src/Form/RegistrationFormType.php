<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationFormType extends AbstractType
{
    private const INPUT_CLASS = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addLastnameField($builder);
        $this->addFirstnameField($builder);
        $this->addEmailField($builder);
        $this->addPlainPasswordField($builder);
        $this->addAgreeTermsField($builder);
        $this->addSubmitListener($builder);
    }

    private function addLastnameField(FormBuilderInterface $builder): void
    {
        $builder->add('lastname', null, [
            'label' => $this->translator->trans('Lastname'),
            'attr' => [
                'placeholder' => 'Doe',
                'class' => self::INPUT_CLASS,
            ]
        ]);
    }

    private function addFirstnameField(FormBuilderInterface $builder): void
    {
        $builder->add('firstname', null, [
            'label' => $this->translator->trans('Firstname'),
            'attr' => [
                'placeholder' => 'John',
                'class' => self::INPUT_CLASS,
            ]
        ]);
    }

    private function addEmailField(FormBuilderInterface $builder): void
    {
        $builder->add('email', null, [
            'label' => $this->translator->trans('Email'),
            'attr' => [
                'placeholder' => 'john.doe@example.com',
                'class' => self::INPUT_CLASS,
            ]
        ]);
    }

    private function addPlainPasswordField(FormBuilderInterface $builder): void
    {
        $builder->add('plainPassword', PasswordType::class, [
            // instead of being set onto the object directly,
            // this is read and encoded in the controller
            'mapped' => false,
            'label' => $this->translator->trans('Password'),
            'attr' => [
                'autocomplete' => 'new-password',
                'class' => self::INPUT_CLASS,
            ],
            'constraints' => [
                new NotBlank(message: 'Please enter a password'),
                new Length(
                    min: 6,
                    minMessage: 'Your password should be at least 6 characters',
                    max: 4096,
                ),
            ],
        ]);
    }

    private function addAgreeTermsField(FormBuilderInterface $builder): void
    {
        $builder->add('agreeTerms', CheckboxType::class, [
            'mapped' => false,
            'label' => $this->translator->trans('Agree terms'),
            'constraints' => [
                new IsTrue(message: 'You should agree to our terms'),
            ],
        ]);
    }

    private function addSubmitListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var User $user */
            $user = $event->getData();
            if ($user->getFirstname() && $user->getLastname()) {
                $user->setFullname(trim($user->getLastname() . ' ' . $user->getFirstname()));
            }
            $event->setData($user);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
