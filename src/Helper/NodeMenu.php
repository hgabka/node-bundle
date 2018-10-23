<?php

namespace Hgabka\NodeBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\UtilsBundle\Helper\DomainConfigurationInterface;
use Hgabka\UtilsBundle\Helper\Security\Acl\AclHelper;
use Hgabka\UtilsBundle\Helper\Security\Acl\Permission\PermissionMap;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Repository\NodeRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class NodeMenu
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var Node
     */
    private $currentNode;

    /**
     * @var string
     */
    private $permission = PermissionMap::PERMISSION_VIEW;

    /**
     * @var bool
     */
    private $includeOffline = false;

    /**
     * @var bool
     */
    private $includeHiddenFromNav = false;

    /**
     * @var NodeMenuItem[]
     */
    private $topNodeMenuItems;

    /**
     * @var NodeMenuItem[]
     */
    private $breadCrumb;

    /**
     * @var Node[]
     */
    private $allNodes = [];

    /**
     * @var Node[]
     */
    private $childNodes = [];

    /**
     * @var Node[]
     */
    private $nodesByInternalName = [];

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var NodeMenuItem
     */
    private $rootNodeMenuItem;

    /**
     * @var DomainConfigurationInterface
     */
    private $domainConfiguration;

    /**
     * @param EntityManagerInterface       $em                  The entity manager
     * @param TokenStorageInterface        $tokenStorage        The security token storage
     * @param AclHelper                    $aclHelper           The ACL helper pages
     * @param DomainConfigurationInterface $domainConfiguration The current domain configuration
     */
    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        AclHelper $aclHelper,
        DomainConfigurationInterface $domainConfiguration
    ) {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->aclHelper = $aclHelper;
        $this->domainConfiguration = $domainConfiguration;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @param Node $currentNode
     */
    public function setCurrentNode(Node $currentNode = null)
    {
        $this->currentNode = $currentNode;
    }

    /**
     * @param string $permission
     */
    public function setPermission($permission)
    {
        if ($this->permission !== $permission) {
            // For now reset initialized flag when cached data has to be reset ...
            $this->initialized = false;
        }
        $this->permission = $permission;
    }

    /**
     * @param bool $includeOffline
     */
    public function setIncludeOffline($includeOffline)
    {
        $this->includeOffline = $includeOffline;
    }

    /**
     * @param bool $includeHiddenFromNav
     */
    public function setIncludeHiddenFromNav($includeHiddenFromNav)
    {
        if ($this->includeHiddenFromNav !== $includeHiddenFromNav) {
            // For now reset initialized flag when cached data has to be reset ...
            $this->initialized = false;
        }
        $this->includeHiddenFromNav = $includeHiddenFromNav;
    }

    /**
     * @return NodeMenuItem[]
     */
    public function getTopNodes()
    {
        $this->init();
        if (!is_array($this->topNodeMenuItems)) {
            $this->topNodeMenuItems = [];

            // To be backwards compatible we need to create the top node MenuItems
            if (array_key_exists(0, $this->childNodes)) {
                $topNodeMenuItems = $this->getTopNodeMenuItems();

                $includeHiddenFromNav = $this->includeHiddenFromNav;
                $this->topNodeMenuItems = array_filter(
                    $topNodeMenuItems,
                    function (NodeMenuItem $entry) use ($includeHiddenFromNav) {
                        if ($entry->getNode()->isHiddenFromNav(
                            ) && !$includeHiddenFromNav
                        ) {
                            return false;
                        }

                        return true;
                    }
                );
            }
        }

        return $this->topNodeMenuItems;
    }

    /**
     * @return NodeMenuItem[]
     */
    public function getBreadCrumb()
    {
        $this->init();
        if (!is_array($this->breadCrumb)) {
            $this->breadCrumb = [];

            // @var NodeRepository $repo
            $repo = $this->em->getRepository(Node::class);

            // Generate breadcrumb MenuItems - fetch *all* languages so you can link translations if needed
            $parentNodes = $repo->getAllParents($this->currentNode);
            $parentNodeMenuItem = null;
            // @var Node $parentNode
            foreach ($parentNodes as $parentNode) {
                $nodeTranslation = $parentNode->getNodeTranslation(
                    $this->locale,
                    $this->includeOffline
                );
                if (null !== $nodeTranslation) {
                    $nodeMenuItem = new NodeMenuItem(
                        $parentNode,
                        $nodeTranslation,
                        $parentNodeMenuItem,
                        $this
                    );
                    $this->breadCrumb[] = $nodeMenuItem;
                    $parentNodeMenuItem = $nodeMenuItem;
                }
            }
        }

        return $this->breadCrumb;
    }

    /**
     * @return null|NodeMenuItem
     */
    public function getCurrent()
    {
        $this->init();
        $breadCrumb = $this->getBreadCrumb();
        if (count($breadCrumb) > 0) {
            return $breadCrumb[count($breadCrumb) - 1];
        }

        return null;
    }

    /**
     * @param int $depth
     *
     * @return null|NodeMenuItem
     */
    public function getActiveForDepth($depth)
    {
        $breadCrumb = $this->getBreadCrumb();
        if (count($breadCrumb) >= $depth) {
            return $breadCrumb[$depth - 1];
        }

        return null;
    }

    /**
     * @param Node $node
     * @param bool $includeHiddenFromNav
     *
     * @return NodeMenuItem[]
     */
    public function getChildren(Node $node, $includeHiddenFromNav = true)
    {
        $this->init();
        $children = [];

        if (array_key_exists($node->getId(), $this->childNodes)) {
            $nodes = $this->childNodes[$node->getId()];
            // @var Node $childNode
            foreach ($nodes as $childNode) {
                $nodeTranslation = $childNode->getNodeTranslation(
                    $this->locale,
                    $this->includeOffline
                );
                if (null !== $nodeTranslation) {
                    $children[] = new NodeMenuItem(
                        $childNode,
                        $nodeTranslation,
                        false,
                        $this
                    );
                }
            }

            $children = array_filter(
                $children,
                function (NodeMenuItem $entry) use ($includeHiddenFromNav) {
                    if ($entry->getNode()->isHiddenFromNav(
                        ) && !$includeHiddenFromNav
                    ) {
                        return false;
                    }

                    return true;
                }
            );
        }

        return $children;
    }

    /**
     * @param Node $node
     * @param bool                              $includeHiddenFromNav
     *
     * @return array|NodeMenuItem[]
     */
    public function getSiblings(Node $node, $includeHiddenFromNav = true)
    {
        $this->init();
        $siblings = [];

        if (false !== $parent = $this->getParent($node)) {
            $siblings = $this->getChildren($parent, $includeHiddenFromNav);

            foreach ($siblings as $index => $child) {
                if ($child === $node) {
                    unset($siblings[$index]);
                }
            }
        }

        return $siblings;
    }

    /**
     * @param Node $node
     * @param bool                              $includeHiddenFromNav
     *
     * @return NodeMenuItem
     */
    public function getPreviousSibling(Node $node, $includeHiddenFromNav = true)
    {
        $this->init();

        if (false !== $parent = $this->getParent($node)) {
            $siblings = $this->getChildren($parent, $includeHiddenFromNav);

            foreach ($siblings as $index => $child) {
                if ($child->getNode() === $node && ($index - 1 >= 0)) {
                    return $siblings[$index - 1];
                }
            }
        }

        return false;
    }

    /**
     * @param Node $node
     * @param bool                              $includeHiddenFromNav
     *
     * @return bool|NodeMenuItem
     */
    public function getNextSibling(Node $node, $includeHiddenFromNav = true)
    {
        $this->init();

        if (false !== $parent = $this->getParent($node)) {
            $siblings = $this->getChildren($parent, $includeHiddenFromNav);

            foreach ($siblings as $index => $child) {
                if ($child->getNode() === $node && (($index + 1) < count(
                            $siblings
                        ))
                ) {
                    return $siblings[$index + 1];
                }
            }
        }

        return false;
    }

    /**
     * @param Node $node
     *
     * @return NodeMenuItem
     */
    public function getParent(Node $node)
    {
        $this->init();
        if ($node->getParent() && array_key_exists(
                $node->getParent()->getId(),
                $this->allNodes
            )
        ) {
            return $this->allNodes[$node->getParent()->getId()];
        }

        return false;
    }

    /**
     * @param NodeTranslation $parentNode The parent node
     * @param string          $slug       The slug
     *
     * @return NodeTranslation
     */
    public function getNodeBySlug(NodeTranslation $parentNode, $slug)
    {
        return $this->em->getRepository(NodeTranslation::class)
            ->getNodeTranslationForSlug($slug, $parentNode);
    }

    /**
     * @param string                                        $internalName   The
     *                                                                      internal
     *                                                                      name
     * @param HasNodeInterface|NodeMenuItem|NodeTranslation $parent         The
     *                                                                      parent
     * @param bool                                          $includeOffline
     *
     * @return null|NodeMenuItem
     */
    public function getNodeByInternalName(
        $internalName,
        $parent = null,
        $includeOffline = null
    ) {
        $this->init();
        $resultNode = null;

        if (null === $includeOffline) {
            $includeOffline = $this->includeOffline;
        }

        if (array_key_exists($internalName, $this->nodesByInternalName)) {
            $nodes = $this->nodesByInternalName[$internalName];
            $nodes = array_filter(
                $nodes,
                function (Node $entry) use ($includeOffline) {
                    if ($entry->isDeleted() && !$includeOffline) {
                        return false;
                    }

                    return true;
                }
            );

            if (null !== $parent) {
                /** @var Node $parentNode */
                if ($parent instanceof NodeTranslation) {
                    $parentNode = $parent->getNode();
                } elseif ($parent instanceof NodeMenuItem) {
                    $parentNode = $parent->getNode();
                } elseif ($parent instanceof HasNodeInterface) {
                    $repo = $this->em->getRepository(
                        Node::class
                    );
                    $parentNode = $repo->getNodeFor($parent);
                }

                // Look for a node with the same parent id
                /** @var Node $node */
                foreach ($nodes as $node) {
                    if ($node->getParent()->getId() === $parentNode->getId()) {
                        $resultNode = $node;

                        break;
                    }
                }

                // Look for a node that has an ancestor with the same parent id
                if (null === $resultNode) {
                    // @var Node $n
                    foreach ($nodes as $node) {
                        $tempNode = $node;
                        while (null === $resultNode && null !== $tempNode->getParent()) {
                            $tempParent = $tempNode->getParent();
                            if ($tempParent->getId() === $parentNode->getId()) {
                                $resultNode = $node;

                                break;
                            }
                            $tempNode = $tempParent;
                        }
                    }
                }
            } else {
                if (count($nodes) > 0) {
                    $resultNode = $nodes[0];
                }
            }
        }

        if ($resultNode) {
            $nodeTranslation = $resultNode->getNodeTranslation(
                $this->locale,
                $includeOffline
            );
            if (null !== $nodeTranslation) {
                return new NodeMenuItem(
                    $resultNode,
                    $nodeTranslation,
                    false,
                    $this
                );
            }
        }

        return null;
    }

    /**
     * Returns the current root node menu item.
     */
    public function getRootNodeMenuItem()
    {
        if (null === $this->rootNodeMenuItem) {
            $rootNode = $this->domainConfiguration->getRootNode();
            if (null !== $rootNode) {
                $nodeTranslation = $rootNode->getNodeTranslation(
                    $this->locale,
                    $this->includeOffline
                );
                $this->rootNodeMenuItem = new NodeMenuItem(
                    $rootNode,
                    $nodeTranslation,
                    false,
                    $this
                );
            } else {
                $this->rootNodeMenuItem = $this->breadCrumb[0];
            }
        }

        return $this->rootNodeMenuItem;
    }

    /**
     * @return bool
     */
    public function isIncludeOffline()
    {
        return $this->includeOffline;
    }

    /**
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @return BaseUser
     */
    public function getUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return TokenStorageInterface
     */
    public function getTokenStorage()
    {
        return $this->tokenStorage;
    }

    /**
     * @return AclHelper
     */
    public function getAclHelper()
    {
        return $this->aclHelper;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return bool
     */
    public function isIncludeHiddenFromNav()
    {
        return $this->includeHiddenFromNav;
    }

    /**
     * Check if provided slug is in active path.
     *
     * @param string $slug
     *
     * @return bool
     */
    public function getActive($slug)
    {
        $bc = $this->getBreadCrumb();
        foreach ($bc as $bcItem) {
            if ($bcItem->getSlug() === $slug) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * This method initializes the nodemenu only once, the method may be
     * executed multiple times.
     */
    private function init()
    {
        if ($this->initialized) {
            return;
        }

        $this->allNodes = [];
        $this->breadCrumb = null;
        $this->childNodes = [];
        $this->topNodeMenuItems = null;
        $this->nodesByInternalName = [];

        // @var NodeRepository $repo
        $repo = $this->em->getRepository(Node::class);

        // Get all possible menu items in one query (also fetch offline nodes)
        $nodes = $repo->getChildNodes(
            false,
            $this->locale,
            $this->permission,
            $this->aclHelper,
            $this->includeHiddenFromNav,
            true,
            $this->domainConfiguration->getRootNode()
        );
        foreach ($nodes as $node) {
            $this->allNodes[$node->getId()] = $node;

            if ($node->getParent()) {
                $this->childNodes[$node->getParent()->getId()][] = $node;
            } else {
                $this->childNodes[0][] = $node;
            }
            $internalName = $node->getInternalName();
            if ($internalName) {
                $this->nodesByInternalName[$internalName][] = $node;
            }
        }
        $this->initialized = true;
    }

    /**
     * @return array
     */
    private function getTopNodeMenuItems()
    {
        $topNodeMenuItems = [];
        $topNodes = $this->childNodes[0];
        // @var Node $topNode
        foreach ($topNodes as $topNode) {
            $nodeTranslation = $topNode->getNodeTranslation(
                $this->locale,
                $this->includeOffline
            );
            if (null !== $nodeTranslation) {
                $topNodeMenuItems[] = new NodeMenuItem(
                    $topNode,
                    $nodeTranslation,
                    null,
                    $this
                );
            }
        }

        return $topNodeMenuItems;
    }
}
