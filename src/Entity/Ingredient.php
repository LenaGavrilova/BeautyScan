<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
#[ORM\Table(name: "ingredients")]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private $name;

    #[ORM\Column(type: "string", length: 50)]
    private $safetyLevel;

    #[ORM\Column(type: "text")]
    private $description;

    #[ORM\OneToMany(mappedBy: "ingredient", targetEntity: IngredientSynonym::class, orphanRemoval: true)]
    private $synonyms;

    public function __construct()
    {
        $this->synonyms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSafetyLevel(): ?string
    {
        return $this->safetyLevel;
    }

    public function setSafetyLevel(string $safetyLevel): self
    {
        $this->safetyLevel = $safetyLevel;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|IngredientSynonym[]
     */
    public function getSynonyms(): Collection
    {
        return $this->synonyms;
    }

    public function addSynonym(IngredientSynonym $synonym): self
    {
        if (!$this->synonyms->contains($synonym)) {
            $this->synonyms[] = $synonym;
            $synonym->setIngredient($this);
        }

        return $this;
    }

    public function removeSynonym(IngredientSynonym $synonym): self
    {
        if ($this->synonyms->removeElement($synonym)) {
            // set the owning side to null (unless already changed)
            if ($synonym->getIngredient() === $this) {
                $synonym->setIngredient(null);
            }
        }

        return $this;
    }
} 