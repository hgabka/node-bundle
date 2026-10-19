<?php

namespace Hgabka\NodeBundle\Tests\Fixtures;

use Hgabka\NodeBundle\Entity\HideFromNodeTreeInterface;
use Hgabka\NodeBundle\Entity\HomePageInterface;

class HomePage extends ContentPage implements HomePageInterface, HideFromNodeTreeInterface
{
    public function isStructureNode(): bool
    {
        return true;
    }
}
