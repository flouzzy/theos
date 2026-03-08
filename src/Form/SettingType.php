<?php

namespace App\Form;

use App\Entity\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brevoListOnboarded', TextType::class, [
                'label' => 'ID Liste Brevo Étudiants Onboarded',
                'required' => false,
                'help' => 'ID de la liste Brevo pour les étudiants en cours de formation.'
            ])
            ->add('brevoListAlumni', TextType::class, [
                'label' => 'ID Liste Brevo Alumni',
                'required' => false,
                'help' => 'ID de la liste Brevo pour les étudiants ayant terminé leur formation.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
        ]);
    }
}
