<?php

namespace Hgabka\NodeBundle\Event;

use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * NodeEvent.
 */
class NodeEvent extends Event
{
    /**
     * @var Response
     */
    private ?Response $response = null;

    public function __construct(
        protected Node $node,
        protected NodeTranslation $nodeTranslation,
        protected NodeVersion $nodeVersion,
        protected HasNodeInterface $page,
    )
    {
    }

    public function getNodeVersion(): NodeVersion
    {
        return $this->nodeVersion;
    }

    public function setNodeVersion(NodeVersion $nodeVersion): self
    {
        $this->nodeVersion = $nodeVersion;

        return $this;
    }

    public function getNode(): Node
    {
        return $this->node;
    }

    public function setNode(Node $node): self
    {
        $this->node = $node;

        return $this;
    }

    public function getNodeTranslation(): NodeTranslation
    {
        return $this->nodeTranslation;
    }


    public function setNodeTranslation(NodeTranslation $nodeTranslation): self
    {
        $this->nodeTranslation = $nodeTranslation;

        return $this;
    }

    public function getPage(): HasNodeInterface
    {
        return $this->page;
    }

    public function setPage(HasNodeInterface $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response): self
    {
        $this->response = $response;

        return $this;
    }
}
