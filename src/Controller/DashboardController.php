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
use Symfony\Component\Security\Http\Attribute\CurrentUser;

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

        // Analytics data for charts
        $analyticsData = $this->generateAnalyticsData();

        return $this->render('dashboard/dashboard.html.twig', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'recentActivities' => $recentActivities,
            'analyticsData' => $analyticsData,
        ]);
    }

    private function generateAnalyticsData(): array
    {
        // Last 7 days sales trend from database
        $salesTrend = [];
        $orderStatusDistribution = [
            'Completed' => 0,
            'Pending' => 0,
            'Cancelled' => 0,
        ];

        // Get all orders and process them
        $allOrders = $this->orderRepository->findAll();
        $ordersBy7Days = [];

        // Initialize last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime('-' . $i . ' days');
            $dateStr = $date->format('M d');
            $ordersBy7Days[$dateStr] = 0;
            $salesTrend[$dateStr] = 0;
        }

        // Process orders
        foreach ($allOrders as $order) {
            // Count order statuses
            $status = $order->getStatus();
            if (isset($orderStatusDistribution[$status])) {
                $orderStatusDistribution[$status]++;
            }

            // Calculate sales trends for last 7 days
            $orderDate = $order->getCreatedAt();
            if ($orderDate) {
                $dateStr = $orderDate->format('M d');
                if (isset($ordersBy7Days[$dateStr])) {
                    $ordersBy7Days[$dateStr]++;
                    $totalAmount = (float)$order->getTotalAmount();
                    if (isset($salesTrend[$dateStr])) {
                        $salesTrend[$dateStr] += $totalAmount;
                    }
                }
            }
        }

        // Format sales trend for charts
        $salesTrendFormatted = [];
        foreach ($salesTrend as $date => $amount) {
            $salesTrendFormatted[] = [
                'date' => $date,
                'amount' => round($amount, 2)
            ];
        }

        // Format order status for pie chart
        $orderStatusFormatted = [];
        foreach ($orderStatusDistribution as $status => $count) {
            $orderStatusFormatted[] = [
                'status' => $status,
                'count' => $count
            ];
        }

        return [
            'salesTrend' => $salesTrendFormatted,
            'orderStatus' => $orderStatusFormatted,
        ];
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
