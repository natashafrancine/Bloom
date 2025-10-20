<?php

namespace App\Entity;

use App\Repository\ShopRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopRepository::class)]
class Shop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    /**
     * @var Collection<int, product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'shop')]
    private Collection $Product;

    public function __construct()
    {
        $this->Product = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, product>
     */
    public function getProduct(): Collection
    {
        return $this->Product;
    }

    public function addProduct(Product $Product): static
    {
        if (!$this->Product->contains($Product)) {
            $this->Product->add($Product);
            $Product->setShop($this);
        }

        return $this;
    }

    public function removeProduct(Product $Product): static
    {
        if ($this->Product->removeElement($Product)) {
            // set the owning side to null (unless already changed)
            if ($Product->getShop() === $this) {
                $Product->setShop(null);
            }
        }

        return $this;
    }
}
