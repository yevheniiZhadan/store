<?php

namespace App\MessageHandler;

use App\Entity\Product;
use App\Service\CompleteOrderService;
use App\Service\ObjectSerializer;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use SharedMessages\Message\OrderMessage;

class OrderMessageHandler implements ConsumerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private CompleteOrderService $completeOrderService,
        private ObjectSerializer $objectSerializer
    ) {
    }

    public function execute(AMQPMessage $msg): void
    {
        $this->logger->info('Order message handler invoked.');
        /** @var OrderMessage $orderMessage */
        $orderMessage = $this->objectSerializer->deserializeObject($msg->getBody(), OrderMessage::class);
        $orderedQuantity = $orderMessage->getQuantity();
        $product = $this->entityManager->getRepository(Product::class)->find($orderMessage->getProductId());

        if (!$product || $orderedQuantity > $product->getQuantity()) {
            $status = OrderMessage::ORDER_FAILED;
        } else {
            $remains = $product->getQuantity() - $orderedQuantity;
            $product->setQuantity($remains);
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $status = OrderMessage::ORDER_PROCESSED;
            $this->logger->info('Updated product # ' . $orderMessage->getProductId());
        }

        $this->completeOrderService->completeOrder($orderMessage->getOrderId(), $status);
    }
}
