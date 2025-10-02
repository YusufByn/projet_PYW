<?php

namespace App\Controller;

use App\Entity\Rent;
use App\Repository\RentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(RentRepository $rentRepository): Response
    {
        $user = $this->getUser();
        $rents = [];

        if ($user) {
            // Emprunts en cours de l'utilisateur connecté
            $rents = $rentRepository->findBy([
                'user' => $user,
                'statut' => 'en_cours',
            ]);
        }

        return $this->render('profile/index.html.twig', [
            'rents' => $rents,
        ]);
    }
}
