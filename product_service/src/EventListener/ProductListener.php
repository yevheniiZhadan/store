<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Service\ObjectSerializer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use SharedMessages\Message\ProductMessage;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
class ProductListener
{
    public function __construct(private Producer $productProducer, private ObjectSerializer $objectSerializer)
    {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args);
    }

    private function handleEvent(LifecycleEventArgs $args): void
    {
        $product = $args->getObject();

        if ($product instanceof Product) {
            $productMessage = (new ProductMessage())
                ->setId($product->getId())
                ->setName($product->getName())
                ->setPrice($product->getPrice())
                ->setQuantity($product->getQuantity());

            $this->productProducer->publish($this->objectSerializer->serializeObject($productMessage));
        }
    }
}
