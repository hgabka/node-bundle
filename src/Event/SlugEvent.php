<?php

namespace Hgabka\NodeBundle\Event;

use Hgabka\NodeBundle\Helper\RenderContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class SlugEvent.
 */
class SlugEvent extends Event
{
    public function __construct(protected ?Response $response = null, protected ?RenderContext $renderContext = null)
    {
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response)
    {
        $this->response = $response;
    }

    public function getRenderContext(): ?RenderContext
    {
        return $this->renderContext;
    }

    public function setRenderContext(?RenderContext $renderContext)
    {
        $this->renderContext = $renderContext;
    }
}
