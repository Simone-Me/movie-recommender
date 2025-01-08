<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MovieFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('favorite_dish', ChoiceType::class, [
                'label' => 'Favorite dish',
                'required' => false,
                'choices' => [
                    'Pizza' => 'IT',
                    'Raclette' => 'FR',
                    'Sushi' => 'JP',
                    'Burger' => 'US',
                    'Tajine' => 'AL',
                    'Paella' => 'ES'
                ],
                'help' => 'Choose a favorite dish as you were to choose your last meal'
            ])
            ->add('holiday', ChoiceType::class, [
                'label' => 'Holiday',
                'required' => false,
                'choices' => [
                    'Mountain' => 'adventure',
                    'Sea' => 'comedy',
                    'Country Side' => 'family',
                    'City' => 'action'
                ],
                'help' => 'Where would you like to go on holiday if the world was on fire'
            ])
            ->add('animal', ChoiceType::class, [
                'label' => 'Animal',
                'required' => false,
                'choices' => [
                    'Cat' => 'Cat',
                    'Dog' => 'Dog',
                    'Horse' => 'Horse',
                    'Elephant' => 'Elephant',
                    'Fox' => 'Fox',
                    'Lion' => 'Lion',
                    'Eagle' => 'Eagle',
                    'Turtle' => 'Turtle',
                    'Shark' => 'Shark',
                    'Whale' => 'Whale'
                ],
                'help' => 'With which animal would you like to be friends'
            ])
            ->add('transport', ChoiceType::class, [
                'label' => 'Transport',
                'required' => false,
                'choices' => [
                    'Train' => 'voteAverage',
                    'Metro' => 'title',
                    'Airplane' => 'popularity',
                    'Bicycle' => 'releaseDate',
                    'On Foot' => 'revenue',
                    'Wheelchair' => 'voteCount'
                ],
                'help' => 'How would you like to spend your day'
            ])
            ->add('dream_job', ChoiceType::class, [
                'label' => 'Dream Job',
                'required' => false,
                'choices' => [
                    'Astronaut for XSpace' => 'Astronaut for XSpace',
                    'Bakery pastry for the President of Republic' => 'Bakery pastry for the President of Republic',
                    'Veterinarian in Congo' => 'Veterinarian in Congo',
                    'Psychiatrist in Harlem' => 'Psychiatrist in Harlem',
                    'Boy Band Singer' => 'Boy Band Singer',
                    'Santa Claus' => 'Santa Claus',
                    'Voice Actor Dora the explorer' => 'Voice Actor Dora the explorer'
                ],
                'help' => 'What would you like to be if you could be anything you want'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Search'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
