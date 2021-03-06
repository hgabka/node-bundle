<?php

namespace Hgabka\NodeBundle\Entity;

use Hgabka\NodeBundle\Helper\RenderContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The Page Interface.
 */
interface PageInterface extends HasNodeInterface
{
    /**
     * @param ContainerInterface $container The Container
     * @param Request            $request   The Request
     * @param RenderContext      $context   The Render context
     *
     * @return RedirectResponse|void
     */
    public function service(ServiceLocator $container, Request $request, RenderContext $context);
}
