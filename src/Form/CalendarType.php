<?php

namespace App\Form;

use App\Entity\Calendar;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalendarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du calendrier',
                'attr' => ['placeholder' => 'ex: Calendrier Promo 2026']
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 3]
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL publique (iCal/Google)',
                'required' => false,
            ])
            ->add('embed', TextareaType::class, [
                'label' => 'Code Embed (Iframe)',
                'required' => false,
                'attr' => ['rows' => 5]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Calendar::class,
        ]);
    }
}
