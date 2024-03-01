<?php

namespace App\Service;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use SharedMessages\Message\CompleteOrderMessage;
use Symfony\Component\Uid\Uuid;

class CompleteOrderService
{
    public function __construct(private Producer $orderCompleteProducer, private ObjectSerializer $objectSerializer)
    {
    }

    public function completeOrder(Uuid $orderId, string $status): void
    {
        $completeOrderMessage = (new CompleteOrderMessage())
            ->setOrderId($orderId)
            ->setStatus($status);

        $this->orderCompleteProducer->publish($this->objectSerializer->serializeObject($completeOrderMessage));
    }
}
