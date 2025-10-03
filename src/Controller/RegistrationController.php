<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\AppCustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        // nouvelle instance de user
        $user = new User(); 
        // on crée un formulaire par rapport a RegistrationFormType et user c'est les infos lié 
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            // on déclare une variable plainPassword qui va chercher dans mon form plainPassword et les informtions avec getData
            // les valeur mise par l'utilisateur en gros
            $plainPassword = $form->get('plainPassword')->getData();

            // on set le mdp avec la method  setPassword et on le hash
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // persist c'est la préparation d'envoie de donné
            $entityManager->persist($user);
            // flush c'est l'éxecution
            $entityManager->flush();

            // do anything else you need here, like send an email

            // login l'utilisateur
            $security->login($user, AppCustomAuthenticator::class, 'main');

            return $this->redirectToRoute('app_clothe_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
