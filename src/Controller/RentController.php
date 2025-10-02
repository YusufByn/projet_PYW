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

    #[Route('/{id}', name: 'app_rent_show', methods: ['GET'])]
    public function show(Rent $rent): Response
    {
        return $this->render('rent/show.html.twig', [
            'rent' => $rent,
        ]);
    }
}
