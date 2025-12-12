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
use App\Service\ActivityLogger;

#[Route('/user')]
class UserController extends AbstractController
{
    private ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $role = $request->query->get('role');
        if ($role) {
            $users = $userRepository->findByRole($role);
        } else {
            $users = $userRepository->findAll();
        }

        // Log activity
        $user = $this->getUser();
        if ($user) {
            $this->activityLogger->log($user, 'Viewed Users', 'Accessed the users page');
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

                // Log activity
                $currentUser = $this->getUser();
                if ($currentUser) {
                    $this->activityLogger->log($currentUser, 'Created User', "Added new user: {$user->getEmail()} with role {$user->getRoles()[0]}");
                }

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

            // Log activity
            $currentUser = $this->getUser();
            if ($currentUser) {
                $this->activityLogger->log($currentUser, 'Updated User', "Modified user: {$user->getEmail()}");
            }

            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/profile/update', name: 'user_profile_update', methods: ['POST'])]
    public function updateProfile(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Check if user is updating their own profile
        if ($this->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $email = $request->request->get('email');
        $password = $request->request->get('password');

        // Update email if provided
        if ($email && $email !== $user->getEmail()) {
            $user->setEmail($email);
        }

        // Update password if provided
        if ($password) {
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
        }

        try {
            $em->flush();
            
            // Log activity
            $currentUser = $this->getUser();
            if ($currentUser) {
                $this->activityLogger->log($currentUser, 'Updated Profile', "Updated profile for user: {$user->getEmail()}");
            }

            return $this->json(['success' => true, 'message' => 'Profile updated successfully']);
        } catch (UniqueConstraintViolationException $e) {
            return $this->json(['success' => false, 'message' => 'Email already exists'], 400);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    #[Route('/{id}/toggle-status', name: 'user_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle_status'.$user->getId(), $request->request->get('_token'))) {
            $newStatus = $user->getStatus() === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
            $user->setStatus($newStatus);
            $em->flush();

            // Log activity
            $currentUser = $this->getUser();
            if ($currentUser) {
                $this->activityLogger->log($currentUser, 'Updated User Status', "Changed {$user->getName()} status to {$newStatus}");
            }

            $this->addFlash('success', "User status updated to {$newStatus}");
        }

        return $this->redirectToRoute('user_index');
    }

    #[Route('/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userEmail = $user->getEmail();
            
            $em->remove($user);
            $em->flush();

            // Log activity
            $currentUser = $this->getUser();
            if ($currentUser) {
                $this->activityLogger->log($currentUser, 'Deleted User', "Removed user: {$userEmail}");
            }

            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('user_index');
    }
}