<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\NodeBundle\Repository\NodeVersionLockRepository;
use Hgabka\UtilsBundle\Entity\EntityInterface;

#[ORM\Entity(repositoryClass: NodeVersionLockRepository::class)]
#[ORM\Table(name: 'hg_node_node_version_lock')]
#[ORM\Index(name: 'nt_owner_public_idx', columns: ['owner', 'node_translation_id', 'public_version'])]
class NodeVersionLock implements EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'owner', type: 'string', length: 255)]
    private ?string $owner = null;

    #[ORM\ManyToOne(targetEntity: NodeTranslation::class)]
    #[ORM\JoinColumn(name: 'node_translation_id', referencedColumnName: 'id')]
    private ?NodeTranslation $nodeTranslation = null;

    #[ORM\Column(name: 'public_version', type: 'boolean')]
    private ?bool $publicVersion = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTime $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function isPublicVersion(): ?bool
    {
        return $this->publicVersion;
    }

    public function setPublicVersion(?bool $publicVersion): self
    {
        $this->publicVersion = $publicVersion;
    }

    public function setOwner(?string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function setNodeTranslation(?NodeTranslation $nodeTranslation = null): self
    {
        $this->nodeTranslation = $nodeTranslation;

        return $this;
    }

    public function getNodeTranslation(): ?NodeTranslation
    {
        return $this->nodeTranslation;
    }
}
