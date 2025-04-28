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

    #[ORM\Column(type: "text")]
    private $traditionalName;

    #[ORM\Column(type: "text")]
    private $latinName;

    #[ORM\Column(type: "text")]
    private $INCIName;

    #[ORM\Column(type: "string", length: 50)]
    private $dangerFactor;

    #[ORM\Column(type: "string", length: 50)]
    private $naturalness;


    #[ORM\Column(type: "text")]
    private $usages;

    #[ORM\Column(type: "text")]
    private $safety;

    public function __construct()
    {

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getTraditionalName()
    {
        return $this->traditionalName;
    }

    /**
     * @param mixed $traditionalName
     */
    public function setTraditionalName($traditionalName): void
    {
        $this->traditionalName = $traditionalName;
    }

    /**
     * @return mixed
     */
    public function getLatinName()
    {
        return $this->latinName;
    }

    /**
     * @param mixed $latinName
     */
    public function setLatinName($latinName): void
    {
        $this->latinName = $latinName;
    }

    /**
     * @return mixed
     */
    public function getINCIName()
    {
        return $this->INCIName;
    }

    /**
     * @param mixed $INCIName
     */
    public function setINCIName($INCIName): void
    {
        $this->INCIName = $INCIName;
    }

    /**
     * @return mixed
     */
    public function getDangerFactor()
    {
        return $this->dangerFactor;
    }

    /**
     * @param mixed $dangerFactor
     */
    public function setDangerFactor($dangerFactor): void
    {
        $this->dangerFactor = $dangerFactor;
    }

    /**
     * @return mixed
     */
    public function getNaturalness()
    {
        return $this->naturalness;
    }

    /**
     * @param mixed $naturalness
     */
    public function setNaturalness($naturalness): void
    {
        $this->naturalness = $naturalness;
    }

    /**
     * @return mixed
     */
    public function getUsages()
    {
        return $this->usages;
    }

    /**
     * @param mixed $usages
     */
    public function setUsages($usages): void
    {
        $this->usages = $usages;
    }

    /**
     * @return mixed
     */
    public function getSafety()
    {
        return $this->safety;
    }

    /**
     * @param mixed $safety
     */
    public function setSafety($safety): void
    {
        $this->safety = $safety;
    }

} 