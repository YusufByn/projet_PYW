<?php

namespace App\Controller;

use App\Entity\Clothe;
use App\Entity\Rent;
use App\Form\ClotheType;
use App\Repository\ClotheRepository;
use App\Repository\RentRepository;
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
        return $this->render('clothe/index.html.twig', [
            'clothes' => $clotheRepository->findAll(),
        ]);
    }

    #[Route('/my-clothes', name: 'app_my_clothes', methods: ['GET'])]
    public function myClothes(ClotheRepository $clotheRepository): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour voir vos vêtements.');
            return $this->redirectToRoute('app_login');
        }

        $clothes = $clotheRepository->findByUser($this->getUser());

        return $this->render('clothe/my_clothes.html.twig', [
            'clothes' => $clothes,
        ]);
    }


    #[Route('/new', name: 'app_clothe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un vêtement.');
            return $this->redirectToRoute('app_login');
        }

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
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier un vêtement.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est le propriétaire du vêtement
        if ($clothe->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres vêtements.');
            return $this->redirectToRoute('app_clothe_index');
        }

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
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer un vêtement.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est le propriétaire du vêtement
        if ($clothe->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres vêtements.');
            return $this->redirectToRoute('app_clothe_index');
        }

        if ($this->isCsrfTokenValid('delete'.$clothe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($clothe);
            $entityManager->flush();
            $this->addFlash('success', 'Vêtement supprimé avec succès.');
        }

        return $this->redirectToRoute('app_clothe_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/borrow', name: 'app_clothe_borrow', methods: ['POST'])]
    public function borrow(Clothe $clothe, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour emprunter un vêtement.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si le vêtement est déjà emprunté
        if ($clothe->getCurrentBorrower() !== null) {
            $this->addFlash('error', 'Ce vêtement est déjà emprunté.');
            return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
        }

        // Vérifier que l'utilisateur n'emprunte pas son propre vêtement
        if ($clothe->getUser() === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas emprunter votre propre vêtement.');
            return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
        }

        // Créer l'emprunt
        $rent = new Rent();
        $rent->setClothes($clothe);
        $rent->setUser($this->getUser());
        $rent->setDateDebut(new \DateTime());
        $rent->setStatut('en_cours');

        // Mettre à jour le vêtement
        $clothe->setCurrentBorrower($this->getUser());

        // Sauvegarder
        $entityManager->persist($rent);
        $entityManager->flush();

        $this->addFlash('success', 'Vêtement emprunté avec succès !');
        return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
    }

    #[Route('/{id}/return', name: 'app_clothe_return', methods: ['POST'])]
    public function return(Clothe $clothe, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier que l'utilisateur est bien l'emprunteur actuel
        if ($clothe->getCurrentBorrower() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas rendre un vêtement que vous n\'avez pas emprunté.');
            return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
        }

        // Trouver l'emprunt actuel et le terminer
        $rentRepository = $entityManager->getRepository(Rent::class);
        $currentRent = $rentRepository->findOneBy([
            'clothes' => $clothe,
            'user' => $this->getUser(),
            'statut' => 'en_cours'
        ]);

        if ($currentRent) {
            $currentRent->setStatut('termine');
            $currentRent->setDateFin(new \DateTime());
        }

        // Libérer le vêtement
        $clothe->setCurrentBorrower(null);

        // Sauvegarder
        $entityManager->flush();

        $this->addFlash('success', 'Vêtement rendu avec succès !');
        return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
    }
}
