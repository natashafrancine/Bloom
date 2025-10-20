<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\ShopRepository;
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

    public function __construct(FileUploader $fileUploader)
    {
        $this->fileUploader = $fileUploader;
    }

    /**
     * 🌸 Display all products (optionally filtered by category)
     */
    #[Route('/', name: 'product_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $categoryId = $request->query->get('category');
        $currentCategory = $categoryId ? $categoryRepository->find($categoryId) : null;

        $product = $currentCategory
            ? $productRepository->findBy(['category' => $currentCategory])
            : $productRepository->findAll();

        $categories = $categoryRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $product,
            'categories' => $categories,
            'currentCategory' => $currentCategory,
        ]);
    }

    /**
     * 🌸 Add new product (with optional shop assignment)
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
            // Handle image upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = $this->fileUploader->upload($imageFile);
                $product->setImage($newFilename);
            }

            // Add to shop if checkbox is checked
            $addToShop = $form->has('shop') ? $form->get('shop')->getData() : false;
            if ($addToShop) {
                $shop = $shopRepository->findOneBy([]); // Fetch the first shop or adjust logic as needed
                if ($shop) {
                    $product->setShop($shop);
                    $shop->addProduct($product);
                }
            }

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
     * 🌸 Show a single product
     */
    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * 🌸 Edit existing product (update shop assignment)
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
            // Handle image upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = $this->fileUploader->upload($imageFile);
                $product->setImage($newFilename);
            }

            // Update shop assignment
            $addToShop = $form->has('shop') ? $form->get('shop')->getData() : false;
            if ($addToShop) {
                $shop = $shopRepository->findOneBy([]);
                if ($shop) {
                    $product->setShop($shop);
                    $shop->addProduct($product);
                }
            } else {
                $product->setShop(null);
            }

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
}
