<?php

namespace Hgabka\NodeBundle\Event;

use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;

/**
 * RecopyPageTranslationNodeEvent.
 */
class RecopyPageTranslationNodeEvent extends NodeEvent
{
    public function __construct(
        Node $node,
        NodeTranslation $nodeTranslation,
        NodeVersion $nodeVersion,
        HasNodeInterface $page,
        private NodeTranslation $originalNodeTranslation,
        private NodeVersion $originalNodeVersion,
        private HasNodeInterface $originalPage,
        private ?string $originalLanguage,
    )
    {
        parent::__construct($node, $nodeTranslation, $nodeVersion, $page);
    }

    public function setOriginalLanguage(?string $originalLanguage): self
    {
        $this->originalLanguage = $originalLanguage;

        return $this;
    }

    public function getOriginalLanguage(): ?string
    {
        return $this->originalLanguage;
    }

    public function setOriginalNodeTranslation(NodeTranslation $originalNodeTranslation): self
    {
        $this->originalNodeTranslation = $originalNodeTranslation;

        return $this;
    }

    public function getOriginalNodeTranslation(): NodeTranslation
    {
        return $this->originalNodeTranslation;
    }

    public function setOriginalPage(HasNodeInterface $originalPage): self
    {
        $this->originalPage = $originalPage;

        return $this;
    }

    public function getOriginalPage(): HasNodeInterface
    {
        return $this->originalPage;
    }

    public function setOriginalNodeVersion(NodeVersion $originalNodeVersion): self
    {
        $this->originalNodeVersion = $originalNodeVersion;

        return $this;
    }

    public function getOriginalNodeVersion(): NodeVersion
    {
        return $this->originalNodeVersion;
    }
}
