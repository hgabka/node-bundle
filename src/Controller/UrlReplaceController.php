<?php

namespace Hgabka\NodeBundle\Controller;

use Hgabka\NodeBundle\Helper\URLHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class UrlReplaceController.
 */
class UrlReplaceController
{
    public function __construct(private URLHelper $urlHelper)
    {
    }

    #[Route('/replace', name: 'HgabkaNodeBundle_urlchooser_replace', condition: 'request.isXmlHttpRequest()')]
    public function replaceURLAction(Request $request)
    {
        $response = new JsonResponse();

        $response->setData(['text' => $this->urlHelper->replaceUrl(\Hgabka\UtilsBundle\Helper\RequestHelper::get($request, 'text'))]);

        return $response;
    }
}
