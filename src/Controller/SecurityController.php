<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_root');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_root');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $formData = $data['registration_form'] ?? [];

            // Validate required fields
            $errors = [];

            if (empty($formData['name'])) {
                $errors[] = 'Full name is required';
            } elseif (strlen($formData['name']) < 2) {
                $errors[] = 'Full name must be at least 2 characters';
            }

            if (empty($formData['email'])) {
                $errors[] = 'Email is required';
            } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address';
            } else {
                $existingUser = $userRepository->findOneBy(['email' => $formData['email']]);
                if ($existingUser) {
                    $errors[] = 'An account with this email already exists';
                }
            }

            if (empty($formData['plainPassword'])) {
                $errors[] = 'Password is required';
            } elseif (strlen($formData['plainPassword']) < 6) {
                $errors[] = 'Password must be at least 6 characters long';
            }

            if (empty($formData['confirmPassword'])) {
                $errors[] = 'Please confirm your password';
            } elseif ($formData['plainPassword'] !== $formData['confirmPassword']) {
                $errors[] = 'Passwords do not match';
            }

            if (empty($formData['agreeTerms'])) {
                $errors[] = 'You must agree to the terms';
            }

            if (empty($errors)) {
                $user = new User();
                $user->setName($formData['name']);
                $user->setEmail($formData['email']);
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $formData['plainPassword']
                    )
                );
                $user->setRoles(['ROLE_USER']);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Registration successful! Please log in with your credentials.');
                return $this->redirectToRoute('app_login');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->render('security/register.html.twig');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
