<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // on declare une vairable error qui va chercher les erreurs lors de la derniere authentification
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Ajouter un message flash d'erreur si il y a une erreur d'authentification
        if ($error) {
            // si l'erreur est une instance de BadCredentialsException donc un mdp incorrect
            if ($error instanceof BadCredentialsException) {
                // on ajoute une flash message d'erreur avec la method addFlash
                $this->addFlash('error', 'Erreur mot de passe');
            // si l'erreur est une instance de UserNotFoundException donc un soucis avec la manière dont il a écrit utilisateur 
            } elseif ($error instanceof UserNotFoundException) {
                // on ajoute une flash message d'erreur avec la method addFlash
                $this->addFlash('error', 'Utilisateur inexistant');
            } else {
                // on ajoute une flash message d'erreur avec la method addFlash
                $this->addFlash('error', 'Erreur de connexion');
            }
        }
        
        // on declare une variable lastUsername qui va chercher le dernier username entré par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
