<?php

namespace App\Controller;


use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile',  methods: ['GET'])]
    public function profileIndex(): Response
    {
        // je déclare une variable user qui me permet avec la method getUser qui est dans abstractController
        // elle me permet bah de prendre l'utilisateur actuel
        $user = $this->getUser();

        // si user est vide donc pas d'utilisateur, on utilise la method redirectToRoute
        // pour rediriger l'utilisateur sur la page de connexion
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // si c'est bon on utilise render donc on envoie dans la view avec la bonne page et les info/data dans un array
        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    
    #[Route('/profile/new/password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function profileChangePassword( Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response 
    {
        // en gros c'est un commentaire pour L'IDE sinon bah il cable genre
        // meme si la method est la bah il va dire je la trouve pas
        /** @var \App\Entity\User $user */
        // dcp on déclare une variable user qui va recup l'utilisateur actuel avec la method getUser
        $user = $this->getUser();

        //si pas d'user alors on return sur la page de login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // declaration d'une variable form qui est créer un formulaire par rapport a ChangePasswordType et user c'est les infos lié 
        $form = $this->createForm(ChangePasswordType::class, $user);
        // handleRequest c'est une methode pour gérer les requête http 
        $form->handleRequest($request);

        // si le formulaire est soumis et valide 
        if ($form->isSubmitted() && $form->isValid()) {
        // je déclare une variable plain qui va chercher dans mon form plainPassword et les informtions avec getData
        // les valeur mise par l'utilisateur en gros
        $plain = $form->get('plainPassword')->getData();

            // condition si plain, genre si on a des valeurs recup dans plain
            if ($plain) {
                // on déclare une variable hashed qui est égal a passwordhasher qui est mon objet UserPasswordinterface
                // avec la method hashPassword pour hash le mdp recup de user les valeur recup dans plain (nouveau mdp)
                $hashed = $passwordHasher->hashPassword($user, $plain);
                // donc on utilise la method setPassword qui est un setter dans user et on lui met la valeur hashed (le mdp hashé)
                $user->setPassword($hashed);
                $em->flush();

                // dans cette instance on utilise la method addFlash qui nous permet d'envoyer un message avec une couleur pour montrer que c'est valider
                $this->addFlash('success', 'Mot de passe modifié avec succès !');
                // on redirige vers app_profile (name de la page profile)
                return $this->redirectToRoute('app_profile');
            }
        }

        // dans cette instance on nous envoie dans la view profile new password
        return $this->render('profile/new_password.html.twig', [
            // on envoie en data/info 
            'form' => $form,
        ]);
    }

}




