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
    /**
     * @var TabPane
     */
    private $tabPane;

    /**
     * @var
     */
    private $page;

    /**
     * @var Node
     */
    private $node;

    /**
     * @var NodeTranslation
     */
    private $nodeTranslation;

    /**
     * @var NodeVersion
     */
    private $nodeVersion;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param TabPane          $tabPane         The tab pane
     * @param HasNodeInterface $page            The page
     * @param Node             $node            The node
     * @param NodeTranslation  $nodeTranslation The node translation
     * @param NodeVersion      $nodeVersion     The node version
     */
    public function __construct(Request $request, TabPane $tabPane, $page = null, Node $node = null, NodeTranslation $nodeTranslation = null, NodeVersion $nodeVersion = null)
    {
        $this->request = $request;
        $this->tabPane = $tabPane;
        $this->page = $page;
        $this->node = $node;
        $this->nodeTranslation = $nodeTranslation;
        $this->nodeVersion = $nodeVersion;
    }

    /**
     * @return Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return NodeTranslation
     */
    public function getNodeTranslation()
    {
        return $this->nodeTranslation;
    }

    /**
     * @return NodeVersion
     */
    public function getNodeVersion()
    {
        return $this->nodeVersion;
    }

    /**
     * @return
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return TabPane
     */
    public function getTabPane()
    {
        return $this->tabPane;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
