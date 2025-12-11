<?php

namespace App\Controller;

use App\Entity\Shop;
use App\Form\ShopType;
use App\Repository\ProductRepository;
use App\Repository\ShopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shop')]
class ShopController extends AbstractController
{
    /**
     * 🌸 Display all shops with their products
     */
    #[Route('/', name: 'shop_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $shopProducts = $productRepository->findBy(['addToShop' => true]);

        return $this->render('shop/index.html.twig', [
            'shopProducts' => $shopProducts,
        ]);
    }

    /**
     * 🌸 Create a new shop
     */
    #[Route('/new', name: 'shop_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $shop = new Shop();
        $form = $this->createForm(ShopType::class, $shop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($shop);
            $em->flush();

            $this->addFlash('success', '🌷 Shop created successfully!');
            return $this->redirectToRoute('shop_index');
        }

        return $this->render('shop/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * 🌸 Show a single shop with its products
     */
    #[Route('/{id}', name: 'shop_show', methods: ['GET'])]
    public function show(Shop $shop): Response
    {
        // Get all products related to this shop
        $products = $shop->getProducts();

        return $this->render('shop/show.html.twig', [
            'shop' => $shop,
            'products' => $products,
        ]);
    }

    /**
     * 🌸 Edit shop details
     */
    #[Route('/{id}/edit', name: 'shop_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Shop $shop, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ShopType::class, $shop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '🪷 Shop updated successfully!');
            return $this->redirectToRoute('shop_index');
        }

        return $this->render('shop/edit.html.twig', [
            'form' => $form->createView(),
            'shop' => $shop,
        ]);
    }

    /**
     * 🗑️ Delete a shop
     */
    #[Route('/{id}/delete', name: 'shop_delete', methods: ['POST'])]
    public function delete(Request $request, Shop $shop, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shop->getId(), $request->request->get('_token'))) {
            $em->remove($shop);
            $em->flush();
            $this->addFlash('success', '🗑️ Shop deleted successfully!');
        }

        return $this->redirectToRoute('shop_index');
    }
}
