<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private OrderRepository $orderRepository,
        private UserRepository $userRepository,
        private CategoryRepository $categoryRepository,
        private ActivityLogRepository $activityLogRepository
    ) {}

    #[Route('/', name: 'app_root')]
    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_ADMIN')]
    #[IsGranted('ROLE_STAFF')]
    public function home(): Response
    {
        $stats = [
            'totalProducts' => $this->productRepository->count([]),
            'totalOrders' => $this->orderRepository->count([]),
            'totalUsers' => $this->userRepository->count([]),
            'totalCategories' => $this->categoryRepository->count([]),
        ];

        $recentOrders = $this->orderRepository->findRecent(5);
        $recentActivities = $this->activityLogRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('dashboard/dashboard.html.twig', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'recentActivities' => $recentActivities,
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('dashboard/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('homepage/contact.html.twig');
    }


}
