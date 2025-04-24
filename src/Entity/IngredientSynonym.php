<?php

namespace App\Entity;

use App\Repository\IngredientSynonymRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IngredientSynonymRepository::class)]
#[ORM\Table(name: "ingredient_synonyms")]
class IngredientSynonym
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private $name;

    #[ORM\ManyToOne(targetEntity: Ingredient::class, inversedBy: "synonyms")]
    #[ORM\JoinColumn(nullable: false)]
    private $ingredient;

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

    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }

    public function setIngredient(?Ingredient $ingredient): self
    {
        $this->ingredient = $ingredient;

        return $this;
    }
} 