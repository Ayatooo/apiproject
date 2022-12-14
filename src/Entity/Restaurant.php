<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\RestaurantRepository;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *     "self",
 *    href = @Hateoas\Route(
 *    "restaurant.getOne",
 *   parameters = {
 *      "idRestaurant" = "expr(object.getId())"
 *  }
 * ),
 *    exclusion = @Hateoas\Exclusion(groups={"showRestaurant"})
 * )
 */
#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
class Restaurant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Le nom du restaurant est obligatoire')]
    #[Assert\NotNull()]
    #[Assert\Length(min: 3, minMessage: 'Le nom du restaurant doit faire au moins 3 caractères')]
    #[ORM\Column(length: 255)]
    #[Serializer\Groups(['showRestaurant'])]
    private ?string $restaurantName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups(['showRestaurant'])]
    private ?string $restaurantLatitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups(['showRestaurant'])]
    private ?string $restaurantLongitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups(['showRestaurant'])]
    private ?string $restaurantDescription = null;

    #[Assert\Length(max: 20, maxMessage: 'Le téléphone ne doit pas faire plus de 20 caractères')]
    #[ORM\Column(length: 255, nullable: true)]
    #[Serializer\Groups(['showRestaurant'])]
    private ?string $restaurantPhone = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Serializer\Groups(['showRestaurant'])]
    private ?float $restaurantDistance = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['showRestaurant'])]
    private ?float $average = null;

    #[Assert\Choice(choices: ["true", "false"], message: 'Le statut doit être true ou false')]
    #[ORM\Column(length: 255, nullable: false)]
    #[Serializer\Groups(['showRestaurant'])]
    private ?string $status = null;

    #[Serializer\Groups(['showRestaurant'])]
    #[ORM\ManyToOne(inversedBy: 'userRestaurant')]
    private ?RestaurantOwner $restaurantOwner = null;

    #[ORM\OneToMany(mappedBy: 'Restaurant', targetEntity: Rates::class)]
    private Collection $rates;


    public function __construct()
    {
        $this->rates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRestaurantName(): ?string
    {
        return $this->restaurantName;
    }

    public function setRestaurantName(string $restaurantName): self
    {
        $this->restaurantName = $restaurantName;

        return $this;
    }

    public function getRestaurantLatitude(): ?string
    {
        return $this->restaurantLatitude;
    }

    public function setRestaurantLatitude(?string $restaurantLatitude): self
    {
        $this->restaurantLatitude = $restaurantLatitude;

        return $this;
    }

    public function getRestaurantLongitude(): ?string
    {
        return $this->restaurantLongitude;
    }

    public function setRestaurantLongitude(?string $restaurantLongitude): self
    {
        $this->restaurantLongitude = $restaurantLongitude;

        return $this;
    }

    public function getRestaurantDescription(): ?string
    {
        return $this->restaurantDescription;
    }

    public function setRestaurantDescription(?string $restaurantDescription): self
    {
        $this->restaurantDescription = $restaurantDescription;

        return $this;
    }

    public function getRestaurantPhone(): ?string
    {
        return $this->restaurantPhone;
    }

    public function setRestaurantPhone(?string $restaurantPhone): self
    {
        $this->restaurantPhone = $restaurantPhone;

        return $this;
    }

    public function isStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRestaurantDistance(): ?float
    {
        return $this->restaurantDistance;
    }

    public function setRestaurantDistance(?float $restaurantDistance): self
    {
        $this->restaurantDistance = $restaurantDistance;

        return $this;
    }

    public function getRestaurantOwner(): ?RestaurantOwner
    {
        return $this->restaurantOwner;
    }

    public function setRestaurantOwner(?RestaurantOwner $restaurantOwner): self
    {
        $this->restaurantOwner = $restaurantOwner;

        return $this;
    }

    /**
     * @return Collection<int, Rates>
     */
    public function getRates(): Collection
    {
        return $this->rates;
    }

    public function addRate(Rates $rate): self
    {
        if (!$this->rates->contains($rate)) {
            $this->rates->add($rate);
            $rate->setRestaurant($this);
            $this->calcAverage();
        }

        return $this;
    }

    public function removeRate(Rates $rate): self
    {
        if ($this->rates->removeElement($rate)) {
            // set the owning side to null (unless already changed)
            if ($rate->getRestaurant() === $this) {
                $rate->setRestaurant(null);
            }
        }

        return $this;
    }

    public function getAverage(): ?float
    {
        return $this->average;
    }

    public function setAverage(?float $average): self
    {
        $this->average = $average;

        return $this;
    }

    public function calcAverage(): void
    {
        $sum = 0;
        $count = 0;
        foreach ($this->rates as $rate) {
            $sum += $rate->getStarsNumber();
            $count++;
        }
        if ($count === 0) {
            return;
        }
        $this->setAverage($sum / $count);
    }
}
