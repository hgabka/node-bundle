<?php

namespace Hgabka\NodeBundle\Tests\Fixtures;

use Hgabka\NodeBundle\Entity\AbstractPage;
use Hgabka\NodeBundle\Helper\RenderContext;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;

class ContentPage extends AbstractPage
{
    public function getDefaultAdminType(): string
    {
        return 'App\\Form\\ContentPageType';
    }

    public function getPossibleChildTypes(): array
    {
        return [
            ['name' => 'Sub page', 'class' => self::class],
            ['class' => HomePage::class],
        ];
    }

    public function isStructureNode(): bool
    {
        return false;
    }

    public function service(ServiceLocator $container, Request $request, RenderContext $context)
    {
    }
}
