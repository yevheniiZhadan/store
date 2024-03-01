<?php

namespace App\MessageHandler;

use App\Entity\Product;
use App\Service\ObjectSerializer;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use SharedMessages\Message\ProductMessage;

class ProductMessageHandler implements ConsumerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ObjectSerializer $objectSerializer
    ) {
    }

    public function execute(AMQPMessage $msg): void
    {
        /** @var ProductMessage $productMessage */
        $productMessage = $this->objectSerializer->deserializeObject($msg->getBody(), ProductMessage::class);
        $this->logger->info('ProductMessageHandler In Order invoked.');
        $product = $this->entityManager->getRepository(Product::class)->find($productMessage->getId());

        if (!$product) {
            $product = (new Product())
                ->setId($productMessage->getId());
        }

        $product
            ->setName($productMessage->getName())
            ->setPrice($productMessage->getPrice())
            ->setQuantity($productMessage->getQuantity());

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->logger->info('Updated  product ' . $product->getId() . ' in order service');
    }
}
