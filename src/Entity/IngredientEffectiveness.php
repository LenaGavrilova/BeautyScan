<?php

namespace App\Entity;

use App\Repository\IngredientEffectivenessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IngredientEffectivenessRepository::class)]
#[ORM\Table(name: "ingredient_effectiveness")]
class IngredientEffectiveness
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string")]
    private $effectivenessName;

    #[ORM\Column(type: "string", length: 255)]
    private $ingredientName;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEffectivenessName(): string
    {
        return $this->effectivenessName;
    }

    public function setEffectivenessName(string $effectivenessName): self
    {
        $this->effectivenessName = $effectivenessName;

        return $this;
    }


    public function getIngredientName(): ?string
    {
        return $this->ingredientName;
    }

    public function setIngredientName(string $ingredientName): self
    {
        $this->ingredientName = $ingredientName;

        return $this;
    }

}