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

// IsGranted controle qui a accès a la page user ici c'est l'admin
#[IsGranted('ROLE_ADMIN')]
#[Route('/user')]
final class UserController extends AbstractController
{   
    // method get, c'est la page qui montre tous les utilisateurs
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        // dans cette instance tu envoies comme data/info users qui est un findAll dans l'obj userepo
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAllWithRelations(),
        ]);
    }

    // page de création d'un nouvelle utilisateur, method get pr afficher et post car on créer avec un form
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // déclare la variable user qui est une nouvelle instance de mon objet user
        $user = new User();
        // déclare la variable form dans cette instance créer moi un formulaire avec la method createform
        // par rapport a UserType et user c'est les infos lié 
        $form = $this->createForm(UserType::class, $user);
        // handleRequest c'est une methode pour gérer les requête http 
        $form->handleRequest($request);

        // si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe si fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            } else {
                // Générer un mot de passe temporaire si aucun n'est fourni
                $user->setPassword($passwordHasher->hashPassword($user, 'temp_password_123'));
            }
            
            $entityManager->persist($user);
            // flush c'est l'éxecution
            $entityManager->flush();

            // une fois que c'est fait on redirige sur la page app_user_index (qui est la page ou on a tous les utilisateurs)
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        // on retourne les infos suivantes dans la vue
        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    // slug avec l'id ici pour montrer un user en tapant son num avec la method get
    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    // slug/modifier un utilisateur 
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

            // on execute l'envoie des données 
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            // sinon si le formulaire est soumis mais pas valide
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            // on déclare une variable errors qui va chercher des erreur
            $errors = $form->getErrors(true);
            // on boucle sur les erreurs
            foreach ($errors as $error) {
                // si l'erreur contient la phrase the values do not match (dans symfony)
                if (strpos($error->getMessage(), 'The values do not match') !== false) {
                    // on ajoute une flash message d'erreur avec la method addFlash
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
