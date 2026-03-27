<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Module;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class CourseType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('modules', EntityType::class, [
                'class' => Module::class,
                'choice_label' => 'title',
                'multiple' => true,
                // https://symfony.com/doc/current/reference/forms/types/collection.html#by-reference
                'by_reference' => false,
            ])
            ->add('itemOrder', null, [
                'attr' => ['class' => 'ion-margin'],
            ])
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'Brouillon' => 'draft',
                    'En cours' => 'progress',
                    'Publié' => 'published',
                    'Privé' => 'private',
                    'Archivé' => 'archived',
                ],
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
                        // maxSize: '1024k',
                        mimeTypes: [
                            'image/webp',
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        mimeTypesMessage: 'Merci de charger une image valide',
                    )
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
