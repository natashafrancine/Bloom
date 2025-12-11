<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\ShopRepository;
use App\Service\ActivityLogger;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    private FileUploader $fileUploader;
    private ActivityLogger $activityLogger;

    public function __construct(FileUploader $fileUploader, ActivityLogger $activityLogger)
    {
        $this->fileUploader = $fileUploader;
        $this->activityLogger = $activityLogger;
    }

    /**
     * 🌸 Display all products (optionally filtered by category)
     */
    #[Route('/', name: 'product_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ShopRepository $shopRepository
    ): Response {
        $categoryId = $request->query->get('category');
        $currentCategory = $categoryId ? $categoryRepository->find($categoryId) : null;

        $products = $currentCategory
            ? $productRepository->findBy(['category' => $currentCategory])
            : $productRepository->findAll();

        $categories = $categoryRepository->findAll();
        $shopProducts = $productRepository->findBy(['addToShop' => true]);
        $shop = $shopRepository->findOneBy([]);

        // Log activity
        $user = $this->getUser();
        if ($user) {
            $this->activityLogger->log($user, 'Viewed Products', 'Accessed the products page');
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'currentCategory' => $currentCategory,
            'shopProducts' => $shopProducts,
            'shop' => $shop,
        ]);
    }

    /**
     * 🌸 Add new product
     */
    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ShopRepository $shopRepository
    ): Response {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $product->setImage($this->fileUploader->upload($imageFile));
            }

            $addToShop = $form->has('addToShop') ? $form->get('addToShop')->getData() : false;
            $product->setAddToShop($addToShop);

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', '✅ Product added successfully!');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * 🌸 Show product
     */
    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', ['product' => $product]);
    }

    /**
     * 🌸 Edit product
     */
    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $em,
        ShopRepository $shopRepository
    ): Response {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) $product->setImage($this->fileUploader->upload($imageFile));

            $addToShop = $form->has('addToShop') ? $form->get('addToShop')->getData() : false;
            $product->setAddToShop($addToShop);

            $em->flush();
            $this->addFlash('success', '🌷 Product updated successfully!');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    /**
     * 🗑️ Delete product
     */
    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete(Product $product, EntityManagerInterface $em): Response
    {
        $em->remove($product);
        $em->flush();
        $this->addFlash('success', '🗑️ Product deleted successfully!');
        return $this->redirectToRoute('product_index');
    }

    /**
     * 🌸 AJAX Toggle Add/Remove Product From Shop
     */
    #[Route('/toggle-shop', name: 'product_toggle_shop', methods: ['POST'])]
    public function toggleShop(
        Request $request,
        ProductRepository $productRepo,
        ShopRepository $shopRepo,
        EntityManagerInterface $em
    ): Response {
        $product = $productRepo->find($request->request->get('id'));

        if (!$product) return new Response('Product not found', 404);

        $status = (bool) $request->request->get('shop');
        $product->setAddToShop($status);

        if ($status) {
            $shop = $shopRepo->findOneBy([]);
            if ($shop) $product->setShop($shop);
        } else {
            $product->setShop(null);
        }

        $em->flush();
        return new Response('OK');
    }
}
