<?php

namespace Hgabka\NodeBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\PageInterface;
use Hgabka\NodeBundle\Entity\Pages\LinkPage;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Hgabka\UtilsBundle\Helper\Security\Acl\AclHelper;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NodeManager
{
    /** @var EntityManagerInterface */
    protected $manager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var URLHelper */
    protected $urlHelper;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * NodeManager constructor.
     */
    public function __construct(EntityManagerInterface $manager, RequestStack $requestStack, UrlGeneratorInterface $router, HgabkaUtils $hgabkaUtils, AclHelper $aclHelper, URLHelper $urlHelper)
    {
        $this->manager = $manager;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->aclHelper = $aclHelper;
        $this->urlHelper = $urlHelper;
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
        $locale = $this->hgabkaUtils->getCurrentLocale($locale);

        $routeParameters = $this->getRouteParametersByInternalName($internalName, $locale, $parameters);

        if (!empty($routeParameters) && !\is_array($routeParameters)) {
            return $routeParameters;
        }

        return $this->router->generate(
            '_slug',
            $routeParameters,
            $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param string $internalName Internal name of the node
     * @param string $locale       Locale
     * @param array  $parameters   (optional) extra parameters
     * @param bool   $relative     (optional) return relative path?
     *
     * @return string
     */
    public function getPathByInternalName($internalName, $locale = null, $parameters = [], $relative = false)
    {
        $locale = $this->hgabkaUtils->getCurrentLocale($locale);

        $routeParameters = $this->getRouteParametersByInternalName($internalName, $locale, $parameters);
        dump($routeParameters);
        if (!empty($routeParameters) && !\is_array($routeParameters)) {
            return $routeParameters;
        }

        return $this->router->generate(
            '_slug',
            $routeParameters,
            $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    /**
     * @param NodeTranslation $nodeTranslation Nodetranslation
     * @param array           $parameters      (optional) extra parameters
     * @param bool            $relative        (optional) return relative path?
     *
     * @return string
     */
    public function getPathByNodeTranslation(NodeTranslation $nodeTranslation, $parameters = [], $relative = false)
    {
        $routeParameters = $this->getRouteParametersByNodeTranslation($nodeTranslation, $parameters);
        if (!empty($routeParameters) && !\is_array($routeParameters)) {
            return $routeParameters;
        }

        return $this->router->generate(
            '_slug',
            $routeParameters,
            $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    /**
     * @param NodeTranslation $nodeTranslation Nodetranslation
     * @param array           $parameters      (optional) extra parameters
     * @param bool            $relative        (optional) return relative path?
     *
     * @return string
     */
    public function getUrlByNodeTranslation(NodeTranslation $nodeTranslation, $parameters = [], $relative = false)
    {
        $routeParameters = $this->getRouteParametersByNodeTranslation($nodeTranslation, $parameters);
        if (!empty($routeParameters) && !\is_array($routeParameters)) {
            return $routeParameters;
        }

        return $this->router->generate(
            '_slug',
            $routeParameters,
            $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param string $internalName
     * @param string $locale
     *
     * @return null|Node
     */
    public function getNodeByInternalName($internalName, $locale = null)
    {
        $locale = $this->hgabkaUtils->getCurrentLocale($locale);

        $nodes =
            $this
                ->manager
                ->getRepository(Node::class)
                ->getNodesByInternalName($internalName, $locale);
        if (!empty($nodes)) {
            return $nodes[0];
        }

        return null;
    }

    /**
     * Get the node translation object based on node id and language.
     *
     * @param int    $nodeId
     * @param string $lang
     *
     * @return NodeTranslation
     */
    public function getNodeTranslationByNodeId($nodeId, $lang)
    {
        $repo = $this->manager->getRepository(NodeTranslation::class);

        return $repo->getNodeTranslationByNodeIdQueryBuilder($nodeId, $lang);
    }

    /**
     * @return null|object
     */
    public function getPageByNodeTranslation(NodeTranslation $nodeTranslation)
    {
        return $nodeTranslation->getRef($this->manager);
    }

    /**
     * @return Node
     */
    public function getNodeFor(PageInterface $page)
    {
        return $this->manager->getRepository(Node::class)->getNodeFor($page);
    }

    /**
     * @return NodeTranslation
     */
    public function getNodeTranslationFor(PageInterface $page)
    {
        return $this->manager->getRepository(NodeTranslation::class)->getNodeTranslationFor($page);
    }

    public function getChildrenByNodeId($nodeId, $lang, $refEntityName = null)
    {
        return $this
            ->manager
            ->getRepository(Node::class)
            ->getChildNodes($nodeId, $lang, PermissionMap::PERMISSION_VIEW, $this->aclHelper, false, false, null, $refEntityName)
        ;
    }

    public function getChildrenByRootNode($rootNode, $lang, $refEntityName = null)
    {
        return $this
            ->manager
            ->getRepository(Node::class)
            ->getChildNodes(false, $lang, PermissionMap::PERMISSION_VIEW, $this->aclHelper, false, false, $rootNode, $refEntityName)
        ;
    }

    public function getChildrenByRootNodeQueryBuilder($rootNode, $lang, $refEntityName = null)
    {
        return $this
            ->manager
            ->getRepository(Node::class)
            ->getChildNodesQueryBuilder(false, $lang, false, false, $rootNode, $refEntityName)
        ;
    }

    protected function getRouteParametersByInternalName($internalName, $locale, $parameters = [])
    {
        $url = '';
        /** @var NodeTranslation $translation */
        $translation =
            $this
                ->manager
                ->getRepository(NodeTranslation::class)
                ->getNodeTranslationByLanguageAndInternalName($locale, $internalName)
        ;

        if (null !== $translation) {
            $remoteUrl = $this->getRemoteUrl($translation);
            if (!empty($remoteUrl)) {
                return $remoteUrl;
            }

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

    protected function getRemoteUrl(NodeTranslation $translation)
    {
        $version = $translation->getNodeVersion('public');
        if ($version && LinkPage::class === $version->getRefEntityName()) {
            /** @var LinkPage $ref */
            $ref = $translation->getRef($this->manager);
            if (!empty($ref->getRemoteUrl())) {
                return $this->urlHelper->replaceUrl($ref->getRemoteUrl());
            }
        }

        return null;
    }

    protected function getRouteParametersByNodeTranslation(NodeTranslation $nodeTranslation, $parameters = [])
    {
        $remoteUrl = $this->getRemoteUrl($nodeTranslation);

        if (!empty($remoteUrl)) {
            return $remoteUrl;
        }

        $url = $nodeTranslation->getUrl();

        return array_merge(
            [
                'url' => $url,
                '_locale' => $nodeTranslation->getLang(),
            ],
            $parameters
        );
    }
}
