<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $role = $request->query->get('role');
        if ($role) {
            $users = $userRepository->findByRole($role);
        } else {
            $users = $userRepository->findAll();
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'current_role' => $role,
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setName($request->request->get('name'));
            $user->setRoles([$request->request->get('role')]);

            $plainPassword = $request->request->get('password');
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                $this->addFlash('error', implode('<br>', $errorMessages));
                return $this->render('user/new.html.twig', [
                    'user' => $user,
                ]);
            }

            try {
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'User created successfully.');
                return $this->redirectToRoute('user_index');
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Email already exists.');
                return $this->render('user/new.html.twig', [
                    'user' => $user,
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while saving the user.');
                return $this->render('user/new.html.twig', [
                    'user' => $user,
                ]);
            }
        }

        return $this->render('user/new.html.twig');
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setName($request->request->get('name'));
            $user->setRoles([$request->request->get('role')]);

            if ($request->request->get('password')) {
                $hashedPassword = $passwordHasher->hashPassword($user, $request->request->get('password'));
                $user->setPassword($hashedPassword);
            }

            $em->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('user_index');
    }
}