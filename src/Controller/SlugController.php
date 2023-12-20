<?php

namespace Hgabka\NodeBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Hgabka\NodeBundle\Event\Events;
use Hgabka\NodeBundle\Event\SlugEvent;
use Hgabka\NodeBundle\Event\SlugSecurityEvent;
use Hgabka\NodeBundle\Helper\NodeMenu;
use Hgabka\NodeBundle\Helper\RenderContext;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
     * SlugController constructor.
     */
    public function __construct(
        protected readonly NodeMenu $nodeMenu,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ManagerRegistry $doctrine
    ) {}

    /**
     * Handle the page requests.
     *
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     *
     */
    public function slug(Request $request, ?string $url = null, bool $preview = false): array|Response
    {
        // @var EntityManager $em
        $em = $this->doctrine->getManager();
        $locale = $request->getLocale();

        // @var NodeTranslation $nodeTranslation
        $nodeTranslation = $request->attributes->get('_nodeTranslation');

        // If no node translation -> 404
        if (!$nodeTranslation) {
            throw $this->createNotFoundException('No page found for slug ' . $url);
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

        $nodeMenu = $this->nodeMenu;
        $nodeMenu->setLocale($locale);
        $nodeMenu->setCurrentNode($node);
        $nodeMenu->setIncludeOffline($preview);

        $eventDispatcher = $this->eventDispatcher;
        $eventDispatcher->dispatch($securityEvent, Events::SLUG_SECURITY);

        $params = [
            'nodetranslation' => $nodeTranslation,
            'slug' => $url,
            'page' => $entity,
            'resource' => $entity,
            'nodemenu' => $nodeMenu,
        ];

        if ($preview) {
            $params['isPreview'] = $preview;
        }
        // render page
        $renderContext = new RenderContext($params);

        if (method_exists($entity, 'getDefaultView')) {
            // @noinspection PhpUndefinedMethodInspection
            $renderContext->setView($entity->getDefaultView());
        }
        $preEvent = new SlugEvent(null, $renderContext);
        $eventDispatcher->dispatch($preEvent, Events::PRE_SLUG_ACTION);
        $renderContext = $preEvent->getRenderContext();

        /** @noinspection PhpUndefinedMethodInspection */
        $response = $entity->service($this->container, $request, $renderContext);

        $postEvent = new SlugEvent($response, $renderContext);
        $eventDispatcher->dispatch($postEvent, Events::POST_SLUG_ACTION);

        $response = $postEvent->getResponse();
        $renderContext = $postEvent->getRenderContext();

        if ($response instanceof Response) {
            return $response;
        }

        $view = $renderContext->getView();
        if (empty($view)) {
            throw $this->createNotFoundException('No page found for slug ' . $url);
        }

        $template = new Template($view);
        $request->attributes->set('_template', $template);

        return $renderContext->getArrayCopy();
    }

    /**
     * @param bool $preview
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
