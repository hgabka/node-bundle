<?php

namespace Hgabka\NodeBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\NodeBundle\Event\Events;
use Hgabka\NodeBundle\Event\SlugEvent;
use Hgabka\NodeBundle\Event\SlugSecurityEvent;
use Hgabka\NodeBundle\Helper\NodeMenu;
use Hgabka\NodeBundle\Helper\RenderContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * This controller is for showing frontend pages based on slugs.
 */
class SlugController extends AbstractController
{
    /**
     * Handle the page requests.
     *
     * @param Request $request The request
     * @param string  $url     The url
     * @param bool    $preview Show in preview mode
     *
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     *
     * @return array|Response
     */
    public function slugAction(Request $request, $url = null, $preview = false)
    {
        // @var EntityManager $em
        $em = $this->getDoctrine()->getManager();
        $locale = $request->getLocale();

        // @var NodeTranslation $nodeTranslation
        $nodeTranslation = $request->attributes->get('_nodeTranslation');

        // If no node translation -> 404
        if (!$nodeTranslation) {
            throw $this->createNotFoundException('No page found for slug '.$url);
        }

        $entity = $this->getPageEntity(
            $request,
            $preview,
            $em,
            $nodeTranslation
        );
        $node = $nodeTranslation->getNode();

        $securityEvent = new SlugSecurityEvent();
        $securityEvent
            ->setNode($node)
            ->setEntity($entity)
            ->setRequest($request)
            ->setNodeTranslation($nodeTranslation);

        $nodeMenu = $this->container->get(NodeMenu::class);
        $nodeMenu->setLocale($locale);
        $nodeMenu->setCurrentNode($node);
        $nodeMenu->setIncludeOffline($preview);

        $eventDispatcher = $this->get('event_dispatcher');
        $eventDispatcher->dispatch(Events::SLUG_SECURITY, $securityEvent);

        //render page
        $renderContext = new RenderContext(
            [
                'nodetranslation' => $nodeTranslation,
                'slug' => $url,
                'page' => $entity,
                'resource' => $entity,
                'nodemenu' => $nodeMenu,
            ]
        );
        if (method_exists($entity, 'getDefaultView')) {
            // @noinspection PhpUndefinedMethodInspection
            $renderContext->setView($entity->getDefaultView());
        }
        $preEvent = new SlugEvent(null, $renderContext);
        $eventDispatcher->dispatch(Events::PRE_SLUG_ACTION, $preEvent);
        $renderContext = $preEvent->getRenderContext();

        /** @noinspection PhpUndefinedMethodInspection */
        $response = $entity->service($this->container, $request, $renderContext);

        $postEvent = new SlugEvent($response, $renderContext);
        $eventDispatcher->dispatch(Events::POST_SLUG_ACTION, $postEvent);

        $response = $postEvent->getResponse();
        $renderContext = $postEvent->getRenderContext();

        if ($response instanceof Response) {
            return $response;
        }

        $view = $renderContext->getView();
        if (empty($view)) {
            throw $this->createNotFoundException('No page found for slug '.$url);
        }

        $template = new Template([]);
        $template->setTemplate($view);

        $request->attributes->set('_template', $template);

        return $renderContext->getArrayCopy();
    }

    /**
     * @param Request                $request
     * @param bool                   $preview
     * @param EntityManagerInterface $em
     * @param NodeTranslation        $nodeTranslation
     *
     * @return HasNodeInterface
     */
    private function getPageEntity(Request $request, $preview, EntityManagerInterface $em, NodeTranslation $nodeTranslation)
    {
        // @var HasNodeInterface $entity
        $entity = null;
        if ($preview) {
            $version = $request->get('version');
            if (!empty($version) && is_numeric($version)) {
                $nodeVersion = $em->getRepository(NodeVersion::class)->find($version);
                if (null !== $nodeVersion) {
                    $entity = $nodeVersion->getRef($em);
                }
            }
        }
        if (null === $entity) {
            $entity = $nodeTranslation->getPublicNodeVersion()->getRef($em);

            return $entity;
        }

        return $entity;
    }
}
