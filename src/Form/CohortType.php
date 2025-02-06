<?php

namespace App\Form;

use App\Entity\Cohort;
use App\Entity\Course;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'Brouillon' => 'draft',
                    'Publié' => 'published',
                    'Privé' => 'private',
                    'Archivé' => 'archived',
                ],
            ])
            ->add('courses', EntityType::class, [
                'class' => Course::class,
                'choice_label' => 'title',
                'multiple' => true,
            ])
            ->add('users', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullname',
                'multiple' => true,
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
