<?php

namespace Hgabka\NodeBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NodeManager
{
    /** @var EntityManagerInterface */
    protected $manager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * NodeManager constructor.
     *
     * @param EntityManagerInterface $manager
     * @param RequestStack           $requestStack
     */
    public function __construct(EntityManagerInterface $manager, RequestStack $requestStack, UrlGeneratorInterface $router)
    {
        $this->manager = $manager;
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public function getNodeDataByInternalName($internalName, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->requestStack->getCurrentRequest()->getLocale();
        }

        $nodes = $this
            ->manager
            ->getRepository(Node::class)
            ->getNodesByInternalName($internalName, $locale)
        ;

        if (!empty($nodes)) {
            $node = $nodes[0];
        } else {
            $node = null;
        }

        if (empty($node)) {
            return [
                'node' => $node,
                'nodeTranslation' => null,
                'page' => null,
            ];
        }
        $nodeTrans = $node->getNodeTranslation($locale);

        return [
            'node' => $node,
            'nodeTranslation' => $nodeTrans,
            'page' => $nodeTrans->getRef($this->manager),
        ];
    }

    public function getUrlByInternalName($internalName, $locale = null, $parameters = [], $schemeRelative = false)
    {
        if (null === $locale) {
            $locale = $this->requestStack->getCurrentRequest()->getLocale();
        }

        $routeParameters = $this->getRouteParametersByInternalName($internalName, $locale, $parameters);

        return $this->router->generate(
            '_slug',
            $routeParameters,
            $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    protected function getRouteParametersByInternalName($internalName, $locale, $parameters = [])
    {
        $url = '';
        $translation =
            $this
                ->manager
                ->getRepository(NodeTranslation::class)
                ->getNodeTranslationByLanguageAndInternalName($locale, $internalName)
        ;

        if (null !== $translation) {
            $url = $translation->getUrl();
        }

        return array_merge(
            [
                'url' => $url,
                '_locale' => $locale,
            ],
            $parameters
        );
    }
}
