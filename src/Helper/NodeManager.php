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

    /** @var array */
    protected $nodeDataByInternalName = [];

    protected $urlsByNodeTranslation = [];

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

    public function getNodeDataByInternalName(string $internalName, ?string $locale = null)
    {
        if (null === $locale) {
            $locale = $this->requestStack->getCurrentRequest()->getLocale();
        }

        if (!array_key_exists($internalName, $this->nodeDataByInternalName) || !array_key_exists($locale, $this->nodeDataByInternalName[$internalName])) {
            $nodes = $this
                ->manager
                ->getRepository(Node::class)
                ->getNodesByInternalName($internalName, $locale);

            if (!empty($nodes)) {
                $node = $nodes[0];
            } else {
                $node = null;
            }
            $this->nodeDataByInternalName[$internalName][$locale] = $this->getNodeDataByNode($node, $locale);
        }

        return $this->nodeDataByInternalName[$internalName][$locale];
    }

    public function getNodeDataByNode(?Node $node, ?string $locale = null)
    {
        if (null === $locale) {
            $locale = $this->requestStack->getCurrentRequest()->getLocale();
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
            'page' => $nodeTrans ? $nodeTrans->getRef($this->manager) : null,
        ];
    }

    public function getUrlByInternalName(string $internalName, ?string $locale = null, array $parameters = [], bool $schemeRelative = false)
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
    public function getPathByInternalName(string $internalName, ?string $locale = null, array $parameters = [], bool $relative = false)
    {
        $locale = $this->hgabkaUtils->getCurrentLocale($locale);

        $routeParameters = $this->getRouteParametersByInternalName($internalName, $locale, $parameters);

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
    public function getPathByNodeTranslation(NodeTranslation $nodeTranslation, array $parameters = [], bool $relative = false)
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
    public function getUrlByNodeTranslation(NodeTranslation $nodeTranslation, array $parameters = [], bool $relative = false)
    {
        $routeParameters = $this->getRouteParametersByNodeTranslation($nodeTranslation, $parameters);
        if (!empty($routeParameters) && !\is_array($routeParameters)) {
            return $routeParameters;
        }

        return $this->router->generate(
            '_slug',
            $routeParameters,
            $relative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param string $locale
     *
     * @return null|Node
     */
    public function getNodeByInternalName(string $internalName, ?string $locale = null)
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
     * @return NodeTranslation
     */
    public function getNodeTranslationByNodeId(int $nodeId, string $lang)
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

    /**
     * @param mixed $includeOffline
     *
     * @return Node[]
     */
    public function getChildrenByNodeId(int $nodeId, string $lang, string|array|null $refEntityName = null, $includeOffline = true)
    {
        return $this
            ->manager
            ->getRepository(Node::class)
            ->getChildNodes($nodeId, $lang, PermissionMap::PERMISSION_VIEW, $this->aclHelper, false, false, null, $refEntityName, $includeOffline)
        ;
    }

    public function getChildrenByRootNode(Node $rootNode, string $lang, string|array|null $refEntityName = null)
    {
        return $this
            ->manager
            ->getRepository(Node::class)
            ->getChildNodes(false, $lang, PermissionMap::PERMISSION_VIEW, $this->aclHelper, false, false, $rootNode, $refEntityName)
        ;
    }

    public function getChildrenByRootNodeQueryBuilder(Node $rootNode, string $lang, string|array|null $refEntityName = null)
    {
        return $this
            ->manager
            ->getRepository(Node::class)
            ->getChildNodesQueryBuilder(false, $lang, false, false, $rootNode, $refEntityName)
        ;
    }

    /**
     * @param Node        $node       Node
     * @param null|string $locale     Locale
     * @param array       $parameters (optional) extra parameters
     * @param bool        $relative   (optional) return relative path?
     *
     * @return string
     */
    public function getPathByNode(Node $node, ?string $locale = null, array $parameters = [], bool $relative = false)
    {
        $nodeTranslation = $this->getNodeTranslationByNodeId($node->getId(), $this->hgabkaUtils->getCurrentLocale($locale));

        if (!$nodeTranslation) {
            return null;
        }

        return $this->getPathByNodeTranslation($nodeTranslation, $parameters, $relative);
    }

    /**
     * @param Node        $node       Node
     * @param null|string $locale     Locale
     * @param array       $parameters (optional) extra parameters
     * @param bool        $relative   (optional) return relative path?
     *
     * @return string
     */
    public function getUrlByNode(Node $node, ?string $locale = null, array $parameters = [], bool $relative = false): ?string
    {
        $nodeTranslation = $this->getNodeTranslationByNodeId($node->getId(), $this->hgabkaUtils->getCurrentLocale($locale));

        if (!$nodeTranslation) {
            return null;
        }

        return $this->getUrlNodeTranslation($nodeTranslation, $parameters, $relative);
    }

    /**
     * @param PageInterface $page       Page
     * @param array         $parameters (optional) extra parameters
     * @param bool          $relative   (optional) return relative path?
     *
     * @return string
     */
    public function getPathByPage(PageInterface $page, array $parameters = [], bool $relative = false): ?string
    {
        $nodeTranslation = $this->getNodeTranslationFor($page);

        if (!$nodeTranslation) {
            return null;
        }

        return $this->getPathByNodeTranslation($nodeTranslation, $parameters, $relative);
    }

    /**
     * @param PageInterface $page       Page
     * @param array         $parameters (optional) extra parameters
     * @param bool          $relative   (optional) return relative path?
     *
     * @return string
     */
    public function getUrlByPage(PageInterface $page, array $parameters = [], bool $relative = false): ?string
    {
        $nodeTranslation = $this->getNodeTranslationFor($page);

        if (!$nodeTranslation) {
            return null;
        }

        return $this->getUrlByNodeTranslation($nodeTranslation, $parameters, $relative);
    }

    public function getTreePathForPage(PageInterface $page, ?int $topLevel = null, bool $includeSelf = false): array
    {
        $node = $this->getNodeFor($page);

        if (!$node) {
            return [];
        }

        return $this->getTreePathForNode($node, $topLevel, $includeSelf);
    }

    public function getTreePathForNode(Node $node, ?int $topLevel = null, bool $includeSelf = false): array
    {
        $parent = $node->getParent();
        $nodes = [];

        while ($parent && (null === $topLevel || (int) $parent->getLevel() >= $topLevel)) {
            array_unshift($nodes, $parent);
            $parent = $parent->getParent();
        }

        if ($includeSelf) {
            $nodes[] = $node;
        }

        return $nodes;
    }

    protected function getRouteParametersByInternalName(string $internalName, string $locale, array $parameters = [])
    {
        $url = '';
        $data = $this->getNodeDataByInternalName($internalName, $locale);
        /** @var NodeTranslation $translation */
        $translation = $data['nodeTranslation'] ?? null;

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
        if (!array_key_exists($translation->getId(), $this->urlsByNodeTranslation)) {
            $version = $translation->getNodeVersion('public');
            if ($version && LinkPage::class === $version->getRefEntityName()) {
                /** @var LinkPage $ref */
                $ref = $translation->getRef($this->manager);
                if (!empty($ref->getRemoteUrl())) {
                    $this->urlsByNodeTranslation[$translation->getId()] = $this->urlHelper->replaceUrl($ref->getRemoteUrl());
                } else {
                    $this->urlsByNodeTranslation[$translation->getId()] = null;
                }
            } else {
                $this->urlsByNodeTranslation[$translation->getId()] = null;
            }
        }

        return $this->urlsByNodeTranslation[$translation->getId()];
    }

    protected function getRouteParametersByNodeTranslation(NodeTranslation $nodeTranslation, array $parameters = [])
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
