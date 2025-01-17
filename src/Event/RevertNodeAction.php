<?php

namespace Hgabka\NodeBundle\Event;

use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;

/**
 * This event will pass metadata when a revert event has been triggered.
 */
class RevertNodeAction extends NodeEvent
{
    public function __construct(
        Node $node,
        NodeTranslation $nodeTranslation,
        NodeVersion $nodeVersion,
        HasNodeInterface $page,
        public NodeVersion $originNodeVersion,
        public HasNodeInterface $originPage,
    )
    {
        parent::__construct($node, $nodeTranslation, $nodeVersion, $page);
    }

    public function setOriginNodeVersion(NodeVersion $originNodeVersion): self
    {
        $this->originNodeVersion = $originNodeVersion;

        return $this;
    }

    public function getOriginNodeVersion(): NodeVersion
    {
        return $this->originNodeVersion;
    }

    public function setOriginPage(HasNodeInterface $originPage): self
    {
        $this->originPage = $originPage;

        return $this;
    }

    public function getOriginPage(): HasNodeInterface
    {
        return $this->originPage;
    }
}
