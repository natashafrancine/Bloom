<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Entity\OrderItem;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $paymentStatus = $request->query->get('payment_status', '');

        $qb = $orderRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC');

        if (!empty($search)) {
            $qb->andWhere('o.customerName LIKE :search OR o.customerEmail LIKE :search OR o.id LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($status)) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $status);
        }

        if (!empty($paymentStatus)) {
            $qb->andWhere('o.paymentStatus = :paymentStatus')
               ->setParameter('paymentStatus', $paymentStatus);
        }

        $orders = $qb->getQuery()->getResult();

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    #[Route('/new', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $subtotal = (float) $request->request->get('subtotal', 0);
            $shippingCost = (float) $request->request->get('shippingCost', 0);
            $taxPercentage = (float) $request->request->get('taxPercentage', 0);

            $taxAmount = $subtotal * ($taxPercentage / 100);
            $totalAmount = $subtotal + $shippingCost + $taxAmount;

            $order = new \App\Entity\Order();
            $order->setCustomerName($request->request->get('customerName'));
            $order->setCustomerEmail($request->request->get('customerEmail'));
            $order->setCustomerPhone($request->request->get('customerPhone'));
            $order->setShippingAddress($request->request->get('shippingAddress'));
            $order->setNotes($request->request->get('notes'));
            $order->setShippingMethod($request->request->get('shippingMethod'));
            $order->setPaymentMethod($request->request->get('paymentMethod'));
            $order->setSubtotal(number_format($subtotal, 2, '.', ''));
            $order->setShippingCost(number_format($shippingCost, 2, '.', ''));
            $order->setTaxAmount(number_format($taxAmount, 2, '.', ''));
            $order->setTotalAmount(number_format($totalAmount, 2, '.', ''));
            $order->setStatus($request->request->get('status', 'pending'));
            $order->setPaymentStatus($request->request->get('paymentStatus', 'pending'));

            $em->persist($order);
            $em->flush();

            $this->addFlash('success', 'Order created successfully.');
            return $this->redirectToRoute('order_index');
        }

        return $this->render('order/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, \App\Entity\Order $order, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $subtotal = (float) $request->request->get('subtotal', 0);
            $shippingCost = (float) $request->request->get('shippingCost', 0);
            $taxPercentage = (float) $request->request->get('taxPercentage', 0);

            $taxAmount = $subtotal * ($taxPercentage / 100);
            $totalAmount = $subtotal + $shippingCost + $taxAmount;

            $order->setCustomerName($request->request->get('customerName'));
            $order->setCustomerEmail($request->request->get('customerEmail'));
            $order->setCustomerPhone($request->request->get('customerPhone'));
            $order->setShippingAddress($request->request->get('shippingAddress'));
            $order->setNotes($request->request->get('notes'));
            $order->setShippingMethod($request->request->get('shippingMethod'));
            $order->setPaymentMethod($request->request->get('paymentMethod'));
            $order->setSubtotal(number_format($subtotal, 2, '.', ''));
            $order->setShippingCost(number_format($shippingCost, 2, '.', ''));
            $order->setTaxAmount(number_format($taxAmount, 2, '.', ''));
            $order->setTotalAmount(number_format($totalAmount, 2, '.', ''));
            $order->setStatus($request->request->get('status', 'pending'));
            $order->setPaymentStatus($request->request->get('paymentStatus', 'pending'));
            $order->setUpdatedAt(new \DateTime());

            $em->flush();

            $this->addFlash('success', 'Order updated successfully.');
            return $this->redirectToRoute('order_index');
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}', name: 'order_delete', methods: ['POST'])]
    public function delete(Request $request, \App\Entity\Order $order, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $em->remove($order);
            $em->flush();

            $this->addFlash('success', 'Order deleted successfully.');
        }

        return $this->redirectToRoute('order_index');
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(int $id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);
        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/checkout', name: 'order_checkout', methods: ['GET', 'POST'])]
    public function checkout(int $id, OrderRepository $orderRepository): Response
    {
        // In a full implementation you'd load the cart by user/session.
        // For now, load an existing order's items if order exists, otherwise empty cart.
        $order = $orderRepository->find($id);
        $cart = [];
        if ($order) {
            // map order items to a simple cart structure for display
            foreach ($order->getOrderItems() as $item) {
                $cart[] = [
                    'product' => $item->getProduct(),
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice(),
                ];
            }
        }

        return $this->render('order/checkout.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/create', name: 'order_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ProductRepository $productRepository): Response
    {
        $data = $request->request;

        // CSRF validation
        if (!$this->isCsrfTokenValid('order_create', $data->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('shop_index');
        }
        $order = new \App\Entity\Order();
        $order->setCustomerName($data->get('customerName', ''));
        $order->setCustomerEmail($data->get('customerEmail', ''));
        $order->setCustomerPhone($data->get('customerPhone', ''));
        $order->setShippingAddress($data->get('shippingAddress', ''));
        $order->setNotes($data->get('notes', null));
        $order->setShippingMethod($data->get('shippingMethod', null));
        $order->setPaymentMethod($data->get('paymentMethod', null));
        // Try to load a cart from the session and compute authoritative totals server-side
        $session = $request->getSession();
        $cart = [];
        if ($session && $session->has('cart')) {
            $cart = $session->get('cart');
        }

        $subtotal = 0.0;
        // Cart may be stored as [productId => quantity] or as array of item arrays
        if (!empty($cart) && is_array($cart)) {
            // Normalize cart entries into [ ['productId'=>id, 'quantity'=>q], ... ]
            $normalized = [];
            // If cart is associative like [productId => qty]
            $isAssoc = array_values($cart) !== $cart;
            if ($isAssoc) {
                foreach ($cart as $key => $val) {
                    if (is_numeric($key)) {
                        $normalized[] = ['productId' => (int) $key, 'quantity' => (int) $val];
                    }
                }
            } else {
                foreach ($cart as $item) {
                    if (is_array($item)) {
                        if (isset($item['product'])) {
                            $normalized[] = ['productId' => (int) $item['product'], 'quantity' => (int) ($item['quantity'] ?? 1)];
                        } elseif (isset($item['productId'])) {
                            $normalized[] = ['productId' => (int) $item['productId'], 'quantity' => (int) ($item['quantity'] ?? 1)];
                        } elseif (isset($item['id'])) {
                            $normalized[] = ['productId' => (int) $item['id'], 'quantity' => (int) ($item['quantity'] ?? 1)];
                        }
                    }
                }
            }

            foreach ($normalized as $entry) {
                $productId = $entry['productId'];
                $quantity = max(0, (int) $entry['quantity']);
                if ($quantity <= 0) {
                    continue;
                }
                $product = $productRepository->find($productId);
                if (!$product) {
                    continue;
                }
                $price = (float) $product->getPrice();
                $subtotal += $price * $quantity;

                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($quantity);
                $orderItem->setPrice(number_format($price, 2, '.', ''));
                $order->addOrderItem($orderItem);
                $em->persist($orderItem);
            }
        }

        // If no cart found or cart empty, fall back to posted totals (best-effort)
        if ($subtotal <= 0) {
            $subtotal = (float) $data->get('subtotal', 0);
        }

        $shipping = 0.0;
        $tax = 0.0;

        // In a real implementation compute shipping/tax based on shippingMethod/address
        $total = $subtotal + $shipping + $tax;

        $order->setSubtotal(number_format($subtotal, 2, '.', ''));
        $order->setShippingCost(number_format($shipping, 2, '.', ''));
        $order->setTaxAmount(number_format($tax, 2, '.', ''));
        $order->setTotalAmount(number_format($total, 2, '.', ''));

        $order->setStatus('pending');
        $order->setPaymentStatus('pending');

        $em->persist($order);
        $em->flush();

        $this->addFlash('success', 'Order created successfully.');

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/cancel', name: 'order_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request, OrderRepository $orderRepository, EntityManagerInterface $em): Response
    {
        $order = $orderRepository->find($id);
        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        // CSRF validation for cancel action
        if (!$this->isCsrfTokenValid('cancel' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('order_show', ['id' => $id]);
        }

        $order->setStatus('cancelled');
        $em->flush();

        $this->addFlash('success', 'Order cancelled successfully.');

        return $this->redirectToRoute('order_show', ['id' => $id]);
    }
}
