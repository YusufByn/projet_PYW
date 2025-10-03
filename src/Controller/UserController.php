<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; 

// pour pas que l'ide bug sinon il devient fou
/** @var \App\Entity\User $user */

// #[IsGranted('ROLE_ADMIN')]
#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // je déclare une variable plainWord qui va chercher dans mon form plainPassword et les informtions avec getData
            // les valeur mise par l'admin en gros
            $plainPassword = $form->get('plainPassword')->getData();
            
            // si on a des valeurs recup dans plainPassword
            if ($plainPassword) {
            // on déclare une variable hashed qui veut dire hashé qui est égal à passwordHasher qui est notre objet UserPasswordHasher
            // on utilise la method hashPassword avec l'utilisateur et le mdp qu'on est allé chercher dans plainPassword
            $hashed = $passwordHasher->hashPassword($user, $plainPassword);
            // puis on dit que dans l'utilisateur on setPassword donc on met comme mdp la variable hashed qui est le nv mdp hashé
            $user->setPassword($hashed);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            // Vérifier si l'erreur concerne les mots de passe non identiques
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                if (strpos($error->getMessage(), 'This value is not valid') !== false || 
                    strpos($error->getMessage(), 'Les valeurs ne correspondent pas') !== false) {
                    $this->addFlash('error', 'Les mots de passe doivent être identiques');
                    break;
                }
            }
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
