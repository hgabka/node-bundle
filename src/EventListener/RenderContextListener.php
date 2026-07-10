<?php

namespace Hgabka\NodeBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\NodeVersion;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Twig\Environment;

class RenderContextListener
{
    public function __construct(protected readonly Environment $templating, protected readonly EntityManagerInterface $em) {}

    public function onKernelView(ViewEvent $event)
    {
        $response = $event->getControllerResult();
        if ($response instanceof Response) {
            // If it's a response, just continue
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->has('_template')) { // template is already set
            return;
        }

        $nodeTranslation = $request->attributes->get('_nodeTranslation');
        if ($nodeTranslation) {
            $entity = $request->attributes->get('_entity');
            $url = $request->attributes->get('url');
            $nodeMenu = $request->attributes->get('_nodeMenu');
            $parameters = $request->attributes->get('_renderContext');

            if (true === \Hgabka\UtilsBundle\Helper\RequestHelper::get($request, 'preview')) {
                $version = \Hgabka\UtilsBundle\Helper\RequestHelper::get($request, 'version');
                if (!empty($version) && is_numeric($version)) {
                    $nodeVersion = $this->em->getRepository(NodeVersion::class)->find($version);
                    if (null !== $nodeVersion) {
                        $entity = $nodeVersion->getRef($this->em);
                    }
                }
            }

            $renderContext = [
                'nodetranslation' => $nodeTranslation,
                'slug' => $url,
                'page' => $entity,
                'resource' => $entity,
                'nodemenu' => $nodeMenu,
            ];

            if (\is_array($parameters) || $parameters instanceof \ArrayObject) {
                $parameters = array_merge($renderContext, (array) $parameters);
            } else {
                $parameters = $renderContext;
            }

            if (\is_array($response)) {
                // If the response is an array, merge with rendercontext
                $parameters = array_merge($parameters, $response);
            }

            $event->setResponse(new Response($this->templating->render($entity->getDefaultView(), $parameters)));
        }
    }
}
