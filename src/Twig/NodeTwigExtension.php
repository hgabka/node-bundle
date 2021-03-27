<?php

namespace Hgabka\NodeBundle\Twig;

use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\PageInterface;
use Hgabka\NodeBundle\Entity\StructureNode;
use Hgabka\NodeBundle\Helper\NodeManager;
use Hgabka\NodeBundle\Helper\NodeMenu;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig_Extension;

/**
 * Extension to fetch node / translation by page in Twig templates.
 */
class NodeTwigExtension extends Twig_Extension
{
    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /**
     * @var NodeMenu
     */
    private $nodeMenu;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /** @var NodeManager */
    private $nodeManager;

    public function __construct(
        NodeMenu $nodeMenu,
        RequestStack $requestStack,
        HgabkaUtils $hgabkaUtils,
        NodeManager $nodeManager
    ) {
        $this->nodeMenu = $nodeMenu;
        $this->requestStack = $requestStack;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->nodeManager = $nodeManager;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'get_node_for',
                [$this, 'getNodeFor']
            ),
            new \Twig_SimpleFunction(
                'get_node_translation_for',
                [$this, 'getNodeTranslationFor']
            ),
            new \Twig_SimpleFunction(
                'get_node_by_internal_name',
                [$this, 'getNodeByInternalName']
            ),
            new \Twig_SimpleFunction(
                'get_url_by_internal_name',
                [$this, 'getUrlByInternalName']
            ),
            new \Twig_SimpleFunction(
                'get_path_by_internal_name',
                [$this, 'getPathByInternalName']
            ),
            new \Twig_SimpleFunction(
                'get_url_by_node_translation',
                [$this, 'getUrlByNodeTranslation']
            ),
            new \Twig_SimpleFunction(
                'get_path_by_node_translation',
                [$this, 'getPathByNodeTranslation']
            ),
            new \Twig_SimpleFunction(
                'get_page_by_node_translation',
                [$this, 'getPageByNodeTranslation']
            ),
            new \Twig_SimpleFunction(
                'get_node_menu',
                [$this, 'getNodeMenu']
            ),
            new \Twig_SimpleFunction(
                'is_structure_node',
                [$this, 'isStructureNode']
            ),
            new \Twig_SimpleFunction(
                'file_exists',
                [$this, 'fileExists']
            ),
            new \Twig_SimpleFunction(
                'get_node_trans_by_node_id',
                [$this, 'getNodeTranslationByNodeId']
            ),
            new \Twig_SimpleFunction(
                'get_children_by_node_id',
                [$this, 'getChildrenByNodeId']
            ),
            new \Twig_SimpleFunction(
                'get_children_by_root_node',
                [$this, 'getChildrenByRootNode']
            ),
        ];
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
        return $this->nodeManager->getNodeTranslationByNodeId($nodeId, $lang);
    }

    /**
     * @return null|object
     */
    public function getPageByNodeTranslation(NodeTranslation $nodeTranslation)
    {
        return $this->nodeManager->getPageByNodeTranslation($nodeTranslation);
    }

    /**
     * @return Node
     */
    public function getNodeFor(PageInterface $page)
    {
        return $this->nodeManager->getNodeFor($page);
    }

    /**
     * @return NodeTranslation
     */
    public function getNodeTranslationFor(PageInterface $page)
    {
        return $this->nodeManager->getNodeTranslationFor($page);
    }

    /**
     * @param string $internalName
     * @param string $locale
     *
     * @return null|Node
     */
    public function getNodeByInternalName($internalName, $locale = null)
    {
        return $this->nodeManager->getNodeByInternalName($internalName, $locale);
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
        return $this->nodeManager->getPathByInternalName($internalName, $locale, $parameters, $relative);
    }

    /**
     * @param string $internalName   Internal name of the node
     * @param string $locale         Locale
     * @param array  $parameters     (optional) extra parameters
     * @param bool   $schemeRelative (optional) return relative scheme?
     *
     * @return string
     */
    public function getUrlByInternalName($internalName, $locale = null, $parameters = [], $schemeRelative = false)
    {
        return $this->nodeManager->getUrlByInternalName($internalName, $locale, $parameters, $schemeRelative);
    }

    /**
     * @param NodeTranslation $nodeTranslation Nodetranslation
     * @param string          $locale          Locale
     * @param array           $parameters      (optional) extra parameters
     * @param bool            $relative        (optional) return relative path?
     *
     * @return string
     */
    public function getPathByNodeTranslation(NodeTranslation $nodeTranslation, $parameters = [], $relative = false)
    {
        return $this->nodeManager->getPathByNodeTranslation($nodeTranslation, $parameters, $relative);
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
        return $this->nodeManager->getUrlNodeTranslation($nodeTranslation, $parameters, $relative);
    }

    /**
     * @param string $locale
     * @param Node   $node
     * @param bool   $includeHiddenFromNav
     *
     * @return NodeMenu
     */
    public function getNodeMenu($locale = null, Node $node = null, $includeHiddenFromNav = false)
    {
        if (null === $locale) {
            $locale = $this->hgabkaUtils->getAdminLocale();
        }
        $request = $this->requestStack->getMasterRequest();
        $isPreview = $request->attributes->has('preview') && true === $request->attributes->get('preview');
        $this->nodeMenu->setLocale($locale);
        $this->nodeMenu->setCurrentNode($node);
        $this->nodeMenu->setIncludeOffline($isPreview);
        $this->nodeMenu->setIncludeHiddenFromNav($includeHiddenFromNav);

        return $this->nodeMenu;
    }

    public function isStructureNode($page)
    {
        return $page instanceof StructureNode;
    }

    public function fileExists($filename)
    {
        return file_exists($filename);
    }

    public function getChildrenByNodeId($nodeId, $lang)
    {
        return
            $this
                ->nodeManager
                ->getChildrenByNodeId($nodeId, $lang)
        ;
    }

    public function getChildrenByRootNode($rootNode, $lang)
    {
        return
            $this
                ->nodeManager
                ->getChildrenByRootNode($rootNode, $lang)
        ;
    }
}
