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

    #[ORM\Column(type: "string", length: 255)]
    private $synonymName;

    #[ORM\Column(type: "string", length: 255)]
    private $ingredientName;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSynonymName(): ?string
    {
        return $this->synonymName;
    }

    public function setSynonymName(string $synonymName): self
    {
        $this->synonymName = $synonymName;

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