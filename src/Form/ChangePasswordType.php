<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // on ajoute l'objet RepeatedType qui nous permet de faire un mot de passe et un mot de passe de confirmation
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                // required true car on a besoin de le mettre
                'required' => true,
                // on ajoute l'objet PasswordType qui nous permet de faire un mot de passe
                // on ajoute l'objet PasswordType qui nous permet de faire un mot de passe de confirmation
                'first_options'  => ['label' => 'Nouveau mot de passe'],
                // on ajoute l'objet PasswordType qui nous permet de faire un mot de passe de confirmation
                'second_options' => ['label' => 'Confirmer le mot de passe'],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
