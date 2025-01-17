<?php

namespace Hgabka\NodeBundle\Event;

use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\UtilsBundle\Helper\FormWidgets\Tabs\TabPane;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event to pass metadata if the adaptForm event is triggered.
 */
class AdaptFormEvent extends Event
{
    public function __construct(
        private readonly Request $request,
        private readonly TabPane $tabPane,
        private mixed $page = null,
        private readonly ?Node $node = null,
        private readonly ?NodeTranslation $nodeTranslation = null,
        private readonly ?NodeVersion $nodeVersion = null)
    {
    }

    /**
     * @return Node
     */
    public function getNode(): ?Node
    {
        return $this->node;
    }

    /**
     * @return NodeTranslation
     */
    public function getNodeTranslation(): ?NodeTranslation
    {
        return $this->nodeTranslation;
    }

    /**
     * @return NodeVersion
     */
    public function getNodeVersion(): ?NodeVersion
    {
        return $this->nodeVersion;
    }

    /**
     * @return
     */
    public function getPage(): mixed
    {
        return $this->page;
    }

    /**
     * @return TabPane
     */
    public function getTabPane(): TabPane
    {
        return $this->tabPane;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
