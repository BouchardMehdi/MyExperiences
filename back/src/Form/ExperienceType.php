<?php

namespace App\Form;

use App\Entity\Experience;
use App\Enum\ExperienceStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExperienceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 6],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'divisor' => 1,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (minutes)',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => ExperienceStatus::cases(),
                'choice_label' => static fn (ExperienceStatus $status): string => $status->label(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Experience::class,
        ]);
    }
}
