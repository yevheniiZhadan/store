<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use SharedMessages\Exception\ProductNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class ProductController extends AbstractController
{
    #[Route('/products', name: 'product_index', methods:['get'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $products = $entityManager
            ->getRepository(Product::class)
            ->findAll();

        return $this->json(['data' => $products]);
    }

    #[Route('/products', name: 'product_create', methods:['post'])]
    public function create(
        EntityManagerInterface $entityManager,
        Request $request,
        ValidatorInterface $validator
    ): JsonResponse {
        $product = new Product();
        $product->setName($request->request->get('name'))
            ->setPrice($request->request->get('price'))
            ->setQuantity($request->request->get('quantity'));

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json($product, Response::HTTP_CREATED);
    }

    #[Route('/products/{uuid}', name: 'product_show', methods:['get'])]
    public function show(EntityManagerInterface $entityManager, Uuid $uuid): JsonResponse
    {
        $product = $entityManager->getRepository(Product::class)->find($uuid);

        if (!$product) {
            throw new ProductNotFoundException($uuid);
        }

        return $this->json($product);
    }
}
