<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity-log')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'activity_log_index', methods: ['GET'])]
    public function index(Request $request, ActivityLogRepository $activityLogRepository, UserRepository $userRepository): Response
    {
        $userId = $request->query->get('user');
        $action = $request->query->get('action');

        $qb = $activityLogRepository->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->orderBy('a.createdAt', 'DESC');

        if ($userId) {
            $qb->andWhere('a.user = :userId')
               ->setParameter('userId', $userId);
        }

        if ($action) {
            $qb->andWhere('a.action LIKE :action')
               ->setParameter('action', '%' . $action . '%');
        }

        $activityLogs = $qb->getQuery()->getResult();
        $users = $userRepository->findAll();

        return $this->render('activity_log/index.html.twig', [
            'activity_logs' => $activityLogs,
            'users' => $users,
            'user_filter' => $userId,
            'action_filter' => $action,
        ]);
    }
}