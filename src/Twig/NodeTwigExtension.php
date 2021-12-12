<?php

namespace Hgabka\NodeBundle\Twig;

use Hgabka\NodeBundle\Entity\AbstractPage;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Entity\PageInterface;
use Hgabka\NodeBundle\Entity\StructureNode;
use Hgabka\NodeBundle\Helper\NodeManager;
use Hgabka\NodeBundle\Helper\NodeMenu;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to fetch node / translation by page in Twig templates.
 */
class NodeTwigExtension extends AbstractExtension
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
            new TwigFunction(
                'get_node_for',
                [$this, 'getNodeFor']
            ),
            new TwigFunction(
                'get_node_translation_for',
                [$this, 'getNodeTranslationFor']
            ),
            new TwigFunction(
                'get_node_by_internal_name',
                [$this, 'getNodeByInternalName']
            ),
            new TwigFunction(
                'get_url_by_internal_name',
                [$this, 'getUrlByInternalName']
            ),
            new TwigFunction(
                'get_path_by_internal_name',
                [$this, 'getPathByInternalName']
            ),
            new TwigFunction(
                'get_url_by_node_translation',
                [$this, 'getUrlByNodeTranslation']
            ),
            new TwigFunction(
                'get_path_by_node_translation',
                [$this, 'getPathByNodeTranslation']
            ),
            new TwigFunction(
                'get_url_by_node',
                [$this, 'getUrlByNode']
            ),
            new TwigFunction(
                'get_path_by_node',
                [$this, 'getPathByNode']
            ),
            new TwigFunction(
                'get_url_by_page',
                [$this, 'getUrlByPage']
            ),
            new TwigFunction(
                'get_path_by_page',
                [$this, 'getPathByPage']
            ),
            new TwigFunction(
                'get_page_by_node_translation',
                [$this, 'getPageByNodeTranslation']
            ),
            new TwigFunction(
                'get_node_menu',
                [$this, 'getNodeMenu']
            ),
            new TwigFunction(
                'is_structure_node',
                [$this, 'isStructureNode']
            ),
            new TwigFunction(
                'file_exists',
                [$this, 'fileExists']
            ),
            new TwigFunction(
                'get_node_trans_by_node_id',
                [$this, 'getNodeTranslationByNodeId']
            ),
            new TwigFunction(
                'get_children_by_node_id',
                [$this, 'getChildrenByNodeId']
            ),
            new TwigFunction(
                'get_children_by_root_node',
                [$this, 'getChildrenByRootNode']
            ),
            new TwigFunction(
                'get_page_title',
                [$this, 'getPageTitle']
            ),
        ];
    }

    /**
     * Get the node translation object based on node id and language.
     *
     * @return NodeTranslation
     */
    public function getNodeTranslationByNodeId(int $nodeId, string $lang)
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
     * @param string $locale
     *
     * @return null|Node
     */
    public function getNodeByInternalName(string $internalName, ?string $locale = null)
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
    public function getPathByInternalName(string $internalName, ?string $locale = null, array $parameters = [], bool $relative = false)
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
    public function getUrlByInternalName(string $internalName, ?string $locale = null, array $parameters = [], bool $schemeRelative = false)
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
    public function getPathByNodeTranslation(NodeTranslation $nodeTranslation, array $parameters = [], bool $relative = false)
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
    public function getUrlByNodeTranslation(NodeTranslation $nodeTranslation, array $parameters = [], bool $relative = false)
    {
        return $this->nodeManager->getUrlByNodeTranslation($nodeTranslation, $parameters, $relative);
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
        return $this->nodeManager->getPathByNode($node, $locale, $parameters, $relative);
    }

    /**
     * @param Node        $node       Node
     * @param null|string $locale     Locale
     * @param array       $parameters (optional) extra parameters
     * @param bool        $relative   (optional) return relative path?
     *
     * @return string
     */
    public function getUrlByNode(Node $node, ?string $locale = null, array $parameters = [], bool $relative = false)
    {
        return $this->nodeManager->getUrlByNode($node, $locale, $parameters, $relative);
    }

    /**
     * @param PageInterface $page       Page
     * @param array         $parameters (optional) extra parameters
     * @param bool          $relative   (optional) return relative path?
     *
     * @return string
     */
    public function getPathByPage(PageInterface $page, array $parameters = [], bool $relative = false)
    {
        return $this->nodeManager->getPathByPage($page, $parameters, $relative);
    }

    /**
     * @param PageInterface $page       Page
     * @param array         $parameters (optional) extra parameters
     * @param bool          $relative   (optional) return relative path?
     *
     * @return string
     */
    public function getUrlByPage(PageInterface $page, array $parameters = [], bool $relative = false)
    {
        return $this->nodeManager->getUrlByPage($page, $parameters, $relative);
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
        $request = $this->requestStack->getMainRequest();
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

    public function getChildrenByRootNode($rootNode, $lang, $refEntityName = null)
    {
        return
            $this
                ->nodeManager
                ->getChildrenByRootNode($rootNode, $lang, $refEntityName)
        ;
    }

    public function getPageTitle(AbstractPage $page)
    {
        $pageTitle = $page->getPageTitle();

        return empty($pageTitle) ? $page->getTitle() : $pageTitle;
    }
}
