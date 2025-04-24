<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "analyses")]
class Analysis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    private $queryType;

    #[ORM\Column(type: "text")]
    private $queryContent;

    #[ORM\Column(type: "json")]
    private $result;

    #[ORM\Column(type: "datetime")]
    private $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'analyses')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQueryType(): ?string
    {
        return $this->queryType;
    }

    public function setQueryType(string $queryType): self
    {
        $this->queryType = $queryType;

        return $this;
    }

    public function getQueryContent(): ?string
    {
        return $this->queryContent;
    }

    public function setQueryContent(string $queryContent): self
    {
        $this->queryContent = $queryContent;

        return $this;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }

    public function setResult(array $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
} 