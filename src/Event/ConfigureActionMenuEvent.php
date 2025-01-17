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
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly ItemInterface $menu,
        private readonly ?NodeVersion $activeNodeVersion = null,
    )
    {
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function getMenu(): ItemInterface
    {
        return $this->menu;
    }

    public function getActiveNodeVersion(): ?NodeVersion
    {
        return $this->activeNodeVersion;
    }
}
