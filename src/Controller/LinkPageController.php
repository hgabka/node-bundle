<?php

namespace Hgabka\NodeBundle\Controller;

use Hgabka\NodeBundle\Entity\Pages\LinkPage;
use Hgabka\NodeBundle\Helper\URLHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class LinkPageController extends AbstractController
{
    /** @var URLHelper */
    protected $urlHelper;

    /**
     * LinkPageController constructor.
     */
    public function __construct(URLHelper $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    public function service(Request $request)
    {
        $page = $request->attributes->get('_entity');

        if ($page instanceof LinkPage) {
            return $this->redirect($this->urlHelper->replaceUrl($page->getRemoteUrl()));
        }

        throw $this->createNotFoundException();
    }
}
