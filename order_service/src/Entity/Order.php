<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Assert\Uuid]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Product name must be at least {{ limit }} characters long',
        maxMessage: 'Product name cannot be longer than {{ limit }} characters',
    )]
    private ?string $customerName = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    #[Assert\Expression(
        "this.getProduct().getQuantity() >= this.getQuantityOrdered()",
        message: 'Ordered quantity greater then exist',
    )]
    private ?int $quantityOrdered = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Status $orderStatus = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }
    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): static
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getQuantityOrdered(): ?int
    {
        return $this->quantityOrdered;
    }

    public function setQuantityOrdered(int $quantityOrdered): static
    {
        $this->quantityOrdered = $quantityOrdered;

        return $this;
    }

    public function getOrderStatus(): ?Status
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(?Status $orderStatus): static
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getOrderView(): array
    {
        return [
            'orderId' => $this->getId(),
            'product' => $this->getProduct(),
            'customerName' => $this->getCustomerName(),
            'quantityOrdered' => $this->getQuantityOrdered(),
            'orderStatus' => $this->getOrderStatus()->getName()
        ];
    }
}
