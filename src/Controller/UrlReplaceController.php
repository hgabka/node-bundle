<?php

namespace Hgabka\NodeBundle\Controller;

use Hgabka\NodeBundle\Helper\URLHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UrlReplaceController.
 *
 * @Route(service="hgabka_node.url_replace.controller")
 */
class UrlReplaceController
{
    /**
     * @var URLHelper
     */
    private $urlHelper;

    /**
     * @param URLHelper $urlHelper
     */
    public function __construct(URLHelper $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    /**
     * Render a url with the twig url replace filter.
     *
     * @Route("/replace", name="HgabkaNodeBundle_urlchooser_replace", condition="request.isXmlHttpRequest()")
     */
    public function replaceURLAction(Request $request)
    {
        $response = new JsonResponse();

        $response->setData(['text' => $this->urlHelper->replaceUrl($request->get('text'))]);

        return $response;
    }
}
