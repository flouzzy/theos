<?php

namespace App\Form;

use App\Entity\Calendar;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'événement',
            ])
            ->add('startAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Début',
            ])
            ->add('endAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'label' => 'Fin',
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'label' => 'Lieu / URL de réunion',
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Webinaire' => 'webinar',
                    'Atelier' => 'workshop',
                    'Conférence' => 'conference',
                    'Autre' => 'other',
                ],
                'label' => 'Type d\'événement',
            ])
            ->add('calendar', EntityType::class, [
                'class' => Calendar::class,
                'choice_label' => 'title',
                'label' => 'Calendrier',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
