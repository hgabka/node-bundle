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
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var RenderContext
     */
    protected $renderContext;

    /**
     * @param Response $response
     */
    public function __construct(Response $response = null, RenderContext $renderContext)
    {
        $this->response = $response;
        $this->renderContext = $renderContext;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return RenderContext
     */
    public function getRenderContext()
    {
        return $this->renderContext;
    }

    public function setRenderContext(RenderContext $renderContext)
    {
        $this->renderContext = $renderContext;
    }
}
