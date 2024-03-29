<?php

namespace Hgabka\NodeBundle\Event;

use Hgabka\NodeBundle\Entity\NodeVersion;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * ConfigureActionMenuEvent.
 */
class ConfigureActionMenuEvent extends Event
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ItemInterface
     */
    private $menu;

    /**
     * @var NodeVersion
     */
    private $activeNodeVersion;

    /**
     * @param FactoryInterface $factory           The factory
     * @param ItemInterface    $menu              The menu
     * @param NodeVersion      $activeNodeVersion The nodeversion
     */
    public function __construct(FactoryInterface $factory, ItemInterface $menu, NodeVersion $activeNodeVersion = null)
    {
        $this->factory = $factory;
        $this->menu = $menu;
        $this->activeNodeVersion = $activeNodeVersion;
    }

    /**
     * @return FactoryInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @return ItemInterface
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * Get activeNodeVersion.
     *
     * @return NodeVersion
     */
    public function getActiveNodeVersion()
    {
        return $this->activeNodeVersion;
    }
}
