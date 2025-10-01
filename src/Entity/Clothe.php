<?php

namespace App\Entity;

use App\Repository\ClotheRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClotheRepository::class)]
class Clothe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $img = null;

    #[ORM\ManyToOne(inversedBy: 'clothes')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    private ?User $currentBorrower = null;

    #[ORM\ManyToOne(inversedBy: 'clothes')]
    private ?State $state = null;

    #[ORM\ManyToOne(inversedBy: 'clothes')]
    private ?Category $category = null;

    /**
     * @var Collection<int, Rent>
     */
    #[ORM\OneToMany(targetEntity: Rent::class, mappedBy: 'clothes')]
    private Collection $rents;

    public function __construct()
    {
        $this->rents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): static
    {
        $this->img = $img;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCurrentBorrower(): ?User
    {
        return $this->currentBorrower;
    }

    public function setCurrentBorrower(?User $currentBorrower): static
    {
        $this->currentBorrower = $currentBorrower;

        return $this;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Rent>
     */
    public function getRents(): Collection
    {
        return $this->rents;
    }

    public function addRent(Rent $rent): static
    {
        if (!$this->rents->contains($rent)) {
            $this->rents->add($rent);
            $rent->setClothes($this);
        }

        return $this;
    }

    public function removeRent(Rent $rent): static
    {
        if ($this->rents->removeElement($rent)) {
            // set the owning side to null (unless already changed)
            if ($rent->getClothes() === $this) {
                $rent->setClothes(null);
            }
        }

        return $this;
    }
}
