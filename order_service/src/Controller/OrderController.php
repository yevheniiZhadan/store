<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Status;
use App\Service\ObjectSerializer;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use SharedMessages\Exception\OrderNotFoundException;
use SharedMessages\Exception\ProductNotFoundException;
use SharedMessages\Message\OrderMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class OrderController extends AbstractController
{
    #[Route('/orders', name: 'order_index', methods:['get'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $orders = $entityManager
            ->getRepository(Order::class)
            ->findAll();

        $orderViews = array_map(function (Order $order) {
            return $order->getOrderView();
        }, $orders);

        return $this->json(['data' => $orderViews]);
    }

    #[Route('/orders', name: 'order_create', methods:['post'])]
    public function create(
        EntityManagerInterface $entityManager,
        Request $request,
        ValidatorInterface $validator,
        Producer $orderProducer,
        ObjectSerializer $objectSerializer
    ): JsonResponse {
        $order = new Order();
        $productId = Uuid::fromString($request->request->get('productId'));
        $product = $entityManager->getRepository(Product::class)->find($productId);
        $status = $entityManager->getRepository(Status::class)->findOneBy(['name' => OrderMessage::ORDER_PROCESSING]);

        if (!$product) {
            throw new ProductNotFoundException($productId);
        }

        $order->setCustomerName($request->request->get('customerName'))
            ->setQuantityOrdered($request->request->get('quantityOrdered'))
            ->setOrderStatus($status)
            ->setProduct($product);

        $errors = $validator->validate($order);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        $orderMessage = (new OrderMessage())
            ->setOrderId($order->getId())
            ->setProductId($order->getProduct()->getId())
            ->setQuantity($order->getQuantityOrdered());

        $orderProducer->publish($objectSerializer->serializeObject($orderMessage));

        return $this->json($order->getOrderView(), Response::HTTP_CREATED);
    }

    #[Route('/orders/{uuid}', name: 'order_show', methods:['get'])]
    public function show(EntityManagerInterface $entityManager, Uuid $uuid): JsonResponse
    {
        $order = $entityManager->getRepository(Order::class)->find($uuid);

        if (!$order) {
            throw new OrderNotFoundException($uuid);
        }

        return $this->json($order->getOrderView());
    }
}
