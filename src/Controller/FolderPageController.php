<?php

namespace Hgabka\NodeBundle\Controller;

use Hgabka\NodeBundle\Entity\AbstractFolderPage;
use Hgabka\NodeBundle\Helper\URLHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class FolderPageController extends AbstractController
{
    public function __construct(protected URLHelper $URLHelper)
    {
    }

    public function service(Request $request): RedirectResponse
    {
        /** @var AbstractFolderPage $page */
        $page = $request->attributes->get('_entity');

        if (!empty($page->getRemoteUrl())) {
            return $this->redirect($this->urlHelper->replaceUrl($page->getRemoteUrl()));
        }

        throw $this->createNotFoundException();
    }
}
