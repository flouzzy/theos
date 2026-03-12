<?php

namespace App\Form;

use App\Entity\Cohort;
use App\Entity\Course;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CohortType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('year')
            ->add('startAt', DateType::class, [
                'input'  => 'datetime_immutable'
            ])
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'Brouillon' => 'draft',
                    'Bientot disponible' => 'coming_soon',
                    'Publié' => 'published',
                    'Privé' => 'private',
                    'Archivé' => 'archived',
                ],
            ])
            ->add('image', null, [
                'required' => false,
                'label' => 'Image de couverture (URL)',
            ])
            ->add('courses', EntityType::class, [
                'class' => Course::class,
                'choice_label' => 'title',
                'multiple' => true,
            ])
            ->add('calendar', EntityType::class, [
                'class' => \App\Entity\Calendar::class,
                'choice_label' => 'title',
                'required' => false,
                'placeholder' => 'Sélectionnez un calendrier',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cohort::class,
        ]);
    }
}
