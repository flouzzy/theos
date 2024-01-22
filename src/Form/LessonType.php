<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Entity\Module;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'attr' => ['class' => 'ion-margin'],
            ])
            ->add('description', null, [
                'attr' => [
                    'class' => 'ion-margin text-editor',
                    'rows' => 100, 'cols' => 100
                ],
            ])
            ->add('content', null, [
                'attr' => [
                    'class' => 'ion-margin text-editor',
                    'rows' => 100, 'cols' => 100
                ],
            ])
            ->add('videoEmbeded', null, [
                'attr' => ['rows' => 10, 'cols' => 50],
            ])
            ->add('videoUrl', null, [
                'attr' => ['class' => 'ion-margin'],
            ])
            ->add('module', EntityType::class, [
                'class' => Module::class,
                'choice_label' => 'title',
                'attr' => ['class' => 'ion-margin'],
            ])
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'Brouillon' => 'draft',
                    'Publié' => 'published',
                    'Privé' => 'private',
                    'Archivé' => 'archived',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
