<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function profileIndex(): Response
    {
        // je déclare une variable user qui me permet avec la method getUser qui est dans abstractController
        // elle me permet bah de prendre l'utilisateur actuel
        $user = $this->getUser();

        // si user est vide donc pas d'utilisateur, on utilise la method redirectToRoute
        // pour rediriger l'utilisateur sur la page d'enregistrement
        if (!$user) {
            return $this->redirectToRoute('app_register');
        }

        // si c'est bon on utilise render donc on envoie dans la view avec la bonne page et les info/data dans un array
        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}

