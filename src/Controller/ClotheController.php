<?php

namespace App\Controller;

use App\Entity\Clothe;
use App\Entity\Rent;
use App\Form\ClotheType;
use App\Repository\ClotheRepository;
use App\Repository\RentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clothe')]
final class ClotheController extends AbstractController
{
    #[Route(name: 'app_clothe_index', methods: ['GET'])]
    public function index(ClotheRepository $clotheRepository): Response
    {
        // N'afficher que les vêtements disponibles (non empruntés)
        return $this->render('clothe/index.html.twig', [
            'clothes' => $clotheRepository->findBy(['currentBorrower' => null]),
        ]);
    }

    #[Route('/new', name: 'app_clothe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $clothe = new Clothe();
        
        // Assigner automatiquement l'utilisateur connecté comme propriétaire
        $clothe->setUser($this->getUser());
        $clothe->setCurrentBorrower(null); // Pas d'emprunteur au début
        
        $form = $this->createForm(ClotheType::class, $clothe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = time().'-'.$originalFilename.'.'.$imageFile->guessExtension();
                
                // Définir le dossier d'upload
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/images';
                
                // Déplacer le fichier
                $imageFile->move($uploadDir, $newFilename);
                $clothe->setImg($newFilename);
            }

            $entityManager->persist($clothe);
            $entityManager->flush();

            return $this->redirectToRoute('app_clothe_index');
        }

        return $this->render('clothe/new.html.twig', [
            'clothe' => $clothe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_clothe_show', methods: ['GET'])]
    public function show(Clothe $clothe): Response
    {
        return $this->render('clothe/show.html.twig', [
            'clothe' => $clothe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_clothe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Clothe $clothe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClotheType::class, $clothe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_clothe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('clothe/edit.html.twig', [
            'clothe' => $clothe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_clothe_delete', methods: ['POST'])]
    public function delete(Request $request, Clothe $clothe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$clothe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($clothe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_clothe_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/rent/{id}', name: 'app_clothe_rent', methods: ['POST'])]
    public function rent(Request $request, Clothe $clothe, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('rent'.$clothe->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
        }

        // mettre le clothe en emprunté par qlq
        $clothe->setCurrentBorrower($user);

        $rent = new Rent;
        $rent->setUser($user);
        $rent->setClothes($clothe);
        $rent->setDateDebut(new DateTime());
        $rent->setStatut('en_cours');

        $entityManager->persist($rent);
        $entityManager->flush();

        $this->addFlash('success', 'Vêtement emprunté et retiré du catalogue.');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/return/{id}', name: 'app_clothe_return', methods: ['POST'])]
    public function returnClothe(Request $request, Clothe $clothe, EntityManagerInterface $entityManager, RentRepository $rentRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('return'.$clothe->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $rent = $rentRepository->findOneBy([
            'user' => $user,
            'clothes' => $clothe,
            'statut' => 'en_cours',
        ]);

        if (!$rent) {
            $this->addFlash('danger', 'Aucun emprunt en cours pour cet article.');
            return $this->redirectToRoute('app_profile');
        }

        $clothe->setCurrentBorrower(null);
        $rent->setStatut('rendu');
    
        $entityManager->flush();

        $this->addFlash('success', 'Article rendu et réintégré au catalogue.');
        return $this->redirectToRoute('app_profile');
    }
}
