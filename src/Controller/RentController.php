<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Rent;
use App\Repository\RentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;


#[Route('/rent')]

final class RentController extends AbstractController
{
    #[Route('/rent', name: 'app_rent')]
    public function index(): Response
    {
        return $this->render('rent/index.html.twig', [
            'controller_name' => 'RentController',
        ]);
    }

    #[Route('/my-rents', name: 'app_my_rents', methods: ['GET'])]
    public function myRents(RentRepository $rentRepository): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour voir vos emprunts.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer uniquement les emprunts en cours
        $rents = $rentRepository->findBy([
            'user' => $this->getUser(),
            'statut' => 'en_cours'
        ], ['dateDebut' => 'DESC']);

        return $this->render('rent/my_rents.html.twig', [
            'rents' => $rents,
        ]);
    }

    #[Route('/history', name: 'app_rent_history', methods: ['GET'])]
    public function history(RentRepository $rentRepository): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour voir votre historique.');
            return $this->redirectToRoute('app_login');
        }

        $rents = $rentRepository->findBy(['user' => $this->getUser()], ['dateDebut' => 'DESC']);

        return $this->render('rent/history.html.twig', [
            'rents' => $rents,
        ]);
    }

    #[Route('/{id}', name: 'app_rent_show', methods: ['GET'])]
    public function show(Rent $rent): Response
    {
        return $this->render('rent/show.html.twig', [
            'rent' => $rent,
        ]);
    }
}
