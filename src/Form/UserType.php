<?php

namespace App\Form;

use App\Entity\AvatarFrame;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('firstname')
            ->add('lastname')
            ->add('address', TextareaType::class)
            ->add('birthDate', DateType::class)
            ->add('bio', null, [
                'attr' => ['rows' => 5, 'cols' => 50]
            ])
            ->add('learningManifesto', TextareaType::class, ['required' => false])
            ->add('websiteUrl', null, ['required' => false])
            ->add('githubUrl', null, ['required' => false])
            ->add('isProfilePublic', CheckboxType::class, ['required' => false])
            ->add('activeFrame', EntityType::class, [
                'class' => AvatarFrame::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'No frame',
                'choices' => $options['data'] ? $options['data']->getUnlockedFrames() : [],
                'label' => $this->translator->trans('Avatar Frame'),
            ])
            ->add('emailNotifications', CheckboxType::class, [
                'required' => false,
            ])
            ->add('pushNotifications', CheckboxType::class, [
                'required' => false,
            ])
            ->add('lessonReminders', CheckboxType::class, [
                'required' => false,
            ])
            ->add('weeklySummary', CheckboxType::class, [
                'required' => false,
            ])
            ->add('imageFile', FileType::class, [
                'label' => $this->translator->trans('Choose an image'),

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                'attr' => [
                    'accept' => 'image/*'
                ],

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File(
                        mimeTypes: [
                            'image/webp',
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        mimeTypesMessage: 'Merci de charger une image valide'
                    )
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
