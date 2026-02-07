<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Recipe;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Nom de la recette',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un nom de recette'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('ingredients', TextareaType::class, [
                'label' => 'Ingrédients',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer les ingrédients'),
                ],
                'attr' => ['rows' => 4],
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Instructions',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer les instructions'),
                ],
                'attr' => ['rows' => 5],
            ])
            ->add('preparationTime', IntegerType::class, [
                'label' => 'Temps de préparation (min)',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('cookingTime', IntegerType::class, [
                'label' => 'Temps de cuisson (min)',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'required' => false,
                'attr' => ['min' => 1],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'label',
                'label' => 'Catégorie',
                'required' => true,
                'placeholder' => 'Sélectionner une catégorie',
                'constraints' => [
                    new NotBlank(message: 'Veuillez sélectionner une catégorie'),
                ],
            ])
            ->add('Tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'label',
                'label' => 'Tags',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
