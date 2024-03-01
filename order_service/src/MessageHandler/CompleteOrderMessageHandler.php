<?php

namespace App\MessageHandler;

use App\Entity\Order;
use App\Entity\Status;
use App\Service\ObjectSerializer;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use SharedMessages\Message\CompleteOrderMessage;

class CompleteOrderMessageHandler implements ConsumerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ObjectSerializer $objectSerializer
    ) {
    }

    public function execute(AMQPMessage $msg): void
    {
        $this->logger->info('Complete Order invoked.');
        /** @var CompleteOrderMessage $completeOrderMessage */
        $completeOrderMessage = $this->objectSerializer->deserializeObject($msg->getBody(), CompleteOrderMessage::class);
        $order = $this->entityManager->getRepository(Order::class)->find($completeOrderMessage->getOrderId());

        if ($order) {
            $status = $this->entityManager->getRepository(Status::class)->findOneBy(['name' => $completeOrderMessage->getStatus()]);
            $order->setOrderStatus($status);
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->logger->info('Updated order ' .$order->getId(). ' after processing order.');
        }
    }
}
