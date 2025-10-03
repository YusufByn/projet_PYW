<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // on ajoute l'objet ChoiceType qui nous permet de faire un choix
            ->add('roles', ChoiceType::class, [
                // parmis les choix on a utilisateur qui est ROLE_USER et admin qui est ROLE_ADMIN
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                // on peut faire plusieurs choix avec multiple en true
                'multiple' => true,
                // expanded ca en fait des cases en true
                'expanded' => true,
            ])
            // je remplace le add passwors par plainPassword, avec l'objet RepeatedType pour avoir le "confirmer le mdp"
            ->add('plainPassword', RepeatedType::class, [
                    // le type est l'objet PasswordType 
                    'type' => PasswordType::class,
                    // mapped false car sinon ca va direct toucher a user->password et donc me peter une erreur avec le controller
                    // le controller se chargera de changer le mdp dcp
                    'mapped' => false, 
                    // pas de required psk imagine tu arrives pour juste changer le mail et on te dit le mdp aussi  
                    'required' => false,
                    // lié a notre objet repeatedType, on a notre premiere option qui a pour nom Nouveau MDP
                    'first_options'  => ['label' => 'Nouveau mot de passe'],
                    // lié a notre objet repeatedType, on a notre seconde option qui a pour nom confirmer le MDP
                    'second_options' => ['label' => 'Confirmer le mot de passe'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
