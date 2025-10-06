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
        return $this->render('clothe/index.html.twig', [
            'clothes' => $clotheRepository->findAvailableClothesWithRelations(),
        ]);
    }

    #[Route('/my-clothes', name: 'app_my_clothes', methods: ['GET'])]
    public function myClothes(ClotheRepository $clotheRepository): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour voir vos vêtements.');
            return $this->redirectToRoute('app_login');
        }

        $clothes = $clotheRepository->findByUserWithRelations($this->getUser());

        return $this->render('clothe/my_clothes.html.twig', [
            'clothes' => $clothes,
        ]);
    }

    #[Route('/new', name: 'app_clothe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {

        // création d'une variable user qui va bah aller chercher si un utilisateur est connecé 
        $user = $this->getUser();

        // condition : si pas d'uuser on envoie un msg d'erreur et on redirige sur la page de login
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour créer un vêtement.');
            return $this->redirectToRoute('app_login');
        }

        $clothe = new Clothe();
        $clothe->setUser($user);
        $clothe->setCurrentBorrower(null);
        
        $form = $this->createForm(ClotheType::class, $clothe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Validation du type de fichier
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($imageFile->getMimeType(), $allowedTypes)) {
                    $this->addFlash('error', 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, GIF, WEBP.');
                    return $this->render('clothe/new.html.twig', [
                        'clothe' => $clothe,
                        'form' => $form,
                    ]);
                }
                
                // Validation de la taille (5MB max)
                if ($imageFile->getSize() > 5 * 1024 * 1024) {
                    $this->addFlash('error', 'Le fichier est trop volumineux. Taille maximale : 5MB.');
                    return $this->render('clothe/new.html.twig', [
                        'clothe' => $clothe,
                        'form' => $form,
                    ]);
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = time().'-'.$originalFilename.'.'.$imageFile->guessExtension();
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/images';
                
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
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier un vêtement.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur est admin ou propriétaire du vêtement
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        $isOwner = $clothe->getUser() === $user;

        if (!$isAdmin && !$isOwner) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres vêtements.');
            return $this->redirectToRoute('app_clothe_index');
        }

        $form = $this->createForm(ClotheType::class, $clothe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if ($isAdmin && !$isOwner) {
                $this->addFlash('success', 'Vêtement modifié par l\'administrateur.');
            } else {
                $this->addFlash('success', 'Vêtement modifié avec succès.');
            }

            return $this->redirectToRoute('app_clothe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('clothe/edit.html.twig', [
            'clothe' => $clothe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_clothe_delete', methods: ['POST'])]
    public function delete(Request $request, Clothe $clothe, EntityManagerInterface $entityManager, RentRepository $rentRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer un vêtement.');
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete'.$clothe->getId(), $request->getPayload()->getString('_token'))) {
            // Supprimer d'abord tous les emprunts associés à ce vêtement
            $rents = $rentRepository->findClothesRentsWithRelations($clothe);
            foreach ($rents as $rent) {
                $entityManager->remove($rent);
            }
            
            // Supprimer le vêtement
            $entityManager->remove($clothe);
            $entityManager->flush();
            
            $this->addFlash('success', 'Vêtement et tous ses emprunts supprimés avec succès.');
        }

        return $this->redirectToRoute('app_clothe_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/rendre/{id}', name: 'app_clothe_rendre', methods: ['POST'])]
    public function rendre(Request $request, Clothe $clothe, EntityManagerInterface $entityManager, RentRepository $rentRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('rendre'.$clothe->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $rent = $rentRepository->findUserClotheRentWithRelations($user, $clothe, 'en_cours');

        if (!$rent) {
            $this->addFlash('danger', 'Aucun emprunt en cours pour cet article.');
            return $this->redirectToRoute('app_profile');
        }

        $clothe->setCurrentBorrower(null);
        $rent->setStatut('rendu');
    
        $entityManager->flush();

        $this->addFlash('success', 'Article rendu.');
        return $this->redirectToRoute('app_profile');
    }
}
