<?php

namespace App\Controller;

use App\Entity\Rent;
use App\Entity\Clothe;
use App\Repository\RentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RentController extends AbstractController
{
    #[Route('/my-rents', name: 'app_my_rents', methods: ['GET'])]
    public function myRents(RentRepository $rentRepository): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour voir vos emprunts.');
            return $this->redirectToRoute('app_login');
        }

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
            $this->addFlash('error', 'Vous devez être connecté pour voir l\'historique.');
            return $this->redirectToRoute('app_login');
        }

        $rents = $rentRepository->findBy([
            'user' => $this->getUser()
        ], ['dateDebut' => 'DESC']);

        return $this->render('rent/history.html.twig', [
            'rents' => $rents,
        ]);
    }

    #[Route('/my-requests', name: 'app_my_requests', methods: ['GET'])]
    public function myRequests(RentRepository $rentRepository): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour voir vos demandes.');
            return $this->redirectToRoute('app_login');
        }

        $requests = $rentRepository->createQueryBuilder('r')
            ->leftJoin('r.clothes', 'c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.state', 's')
            ->leftJoin('c.category', 'cat')
            ->addSelect('c', 'u', 's', 'cat')
            ->where('r.user = :user')
            ->andWhere('r.statut = :status')
            ->setParameter('user', $this->getUser())
            ->setParameter('status', 'pending')
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('rent/my_requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/received', name: 'app_rent_received', methods: ['GET'])]
    public function received(RentRepository $rentRepository): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour voir les demandes reçues.');
            return $this->redirectToRoute('app_login');
        }

        // Trouver les demandes où l'utilisateur est le propriétaire du vêtement
        $requests = $rentRepository->createQueryBuilder('r')
            ->leftJoin('r.clothes', 'c')
            ->leftJoin('r.user', 'u')
            ->leftJoin('c.state', 's')
            ->leftJoin('c.category', 'cat')
            ->addSelect('c', 'u', 's', 'cat')
            ->where('c.user = :owner')
            ->andWhere('r.statut = :status')
            ->setParameter('owner', $this->getUser())
            ->setParameter('status', 'pending')
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('rent/received.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/create/{id}', name: 'app_rent_request_create', methods: ['POST'])]
    public function createRequest(Request $request, Clothe $clothe, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour faire une demande.');
            return $this->redirectToRoute('app_login');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('rent_request_'.$clothe->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
        }

        // Vérifier que l'utilisateur ne demande pas son propre vêtement
        if ($clothe->getUser() === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas demander à emprunter votre propre vêtement.');
            return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
        }

        // Vérifier que le vêtement est disponible
        if ($clothe->getCurrentBorrower() !== null) {
            $this->addFlash('error', 'Ce vêtement est déjà emprunté.');
            return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
        }

        // Vérifier qu'il n'y a pas déjà une demande en cours
        $existingRequest = $entityManager->getRepository(Rent::class)->findOneBy([
            'clothes' => $clothe,
            'user' => $this->getUser(),
            'statut' => 'pending'
        ]);

        if ($existingRequest) {
            $this->addFlash('error', 'Vous avez déjà une demande en cours pour ce vêtement.');
            return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
        }

        // Créer la demande
        $rent = new Rent();
        $rent->setClothes($clothe);
        $rent->setUser($this->getUser());
        $rent->setDateDebut(new \DateTime());
        $rent->setStatut('pending');

        $entityManager->persist($rent);
        $entityManager->flush();

        $this->addFlash('success', 'Demande envoyée ! Le propriétaire va être notifié.');
        return $this->redirectToRoute('app_clothe_show', ['id' => $clothe->getId()]);
    }

    #[Route('/approve/{id}', name: 'app_rent_approve', methods: ['POST'])]
    public function approve(Rent $rent, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour approuver une demande.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier que l'utilisateur est le propriétaire du vêtement
        if ($rent->getClothes()->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas approuver cette demande.');
            return $this->redirectToRoute('app_rent_received');
        }

        // Vérifier que la demande est en attente
        if ($rent->getStatut() !== 'pending') {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');
            return $this->redirectToRoute('app_rent_received');
        }

        // Vérifier que le vêtement est toujours disponible
        if ($rent->getClothes()->getCurrentBorrower() !== null) {
            $this->addFlash('error', 'Ce vêtement n\'est plus disponible.');
            return $this->redirectToRoute('app_rent_received');
        }

        // Approuver la demande
        $rent->setStatut('en_cours');
        $rent->getClothes()->setCurrentBorrower($rent->getUser());

        $entityManager->flush();

        $this->addFlash('success', 'Demande approuvée ! Le vêtement est maintenant emprunté.');
        return $this->redirectToRoute('app_rent_received');
    }

    #[Route('/reject/{id}', name: 'app_rent_reject', methods: ['POST'])]
    public function reject(Rent $rent, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour rejeter une demande.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier que l'utilisateur est le propriétaire du vêtement
        if ($rent->getClothes()->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas rejeter cette demande.');
            return $this->redirectToRoute('app_rent_received');
        }

        // Vérifier que la demande est en attente
        if ($rent->getStatut() !== 'pending') {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');
            return $this->redirectToRoute('app_rent_received');
        }

        // Rejeter la demande
        $rent->setStatut('rejected');
        $entityManager->flush();

        $this->addFlash('success', 'Demande rejetée.');
        return $this->redirectToRoute('app_rent_received');
    }
}
