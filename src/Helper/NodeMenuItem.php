<?php

namespace Hgabka\NodeBundle\Helper;

use Doctrine\ORM\EntityManager;
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\Node;
use Hgabka\NodeBundle\Entity\NodeTranslation;

/**
 * NodeMenuItem.
 */
class NodeMenuItem
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Node
     */
    private $node;

    /**
     * @var NodeTranslation
     */
    private $nodeTranslation;

    /**
     * @var NodeMenuItem[]
     */
    private $children;

    /**
     * @var NodeMenuItem
     */
    private $parent;

    /**
     * @var NodeMenu
     */
    private $menu;

    /**
     * @param Node                    $node            The node
     * @param NodeTranslation         $nodeTranslation The nodetranslation
     * @param null|false|NodeMenuItem $parent          The parent nodemenuitem
     * @param NodeMenu                $menu            The menu
     */
    public function __construct(Node $node, NodeTranslation $nodeTranslation, $parent, NodeMenu $menu)
    {
        $this->node = $node;
        $this->nodeTranslation = $nodeTranslation;
        // false = look up parent later if required (default); null = top menu item; NodeMenuItem = parent item already fetched
        $this->parent = $parent;
        $this->menu = $menu;
        $this->em = $menu->getEntityManager();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->node->getId();
    }

    /**
     * @return Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return NodeTranslation
     */
    public function getNodeTranslation()
    {
        return $this->nodeTranslation;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        $nodeTranslation = $this->getNodeTranslation();
        if ($nodeTranslation) {
            return $nodeTranslation->getTitle();
        }

        return 'Untranslated';
    }

    /**
     * @return bool
     */
    public function getOnline()
    {
        $nodeTranslation = $this->getNodeTranslation();
        if ($nodeTranslation) {
            return $nodeTranslation->isOnline();
        }

        return false;
    }

    /**
     * @return null|string
     */
    public function getSlugPart()
    {
        $nodeTranslation = $this->getNodeTranslation();
        if ($nodeTranslation) {
            return $nodeTranslation->getFullSlug();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->getUrl();
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $nodeTranslation = $this->getNodeTranslation();
        if ($nodeTranslation) {
            return $nodeTranslation->getUrl();
        }

        return null;
    }

    /**
     * @return null|NodeMenuItem
     */
    public function getParent()
    {
        if (false === $this->parent) {
            // We need to calculate the parent
            $this->parent = $this->menu->getParent($this->node);
        }

        return $this->parent;
    }

    /**
     * @param null|false|NodeMenuItem $parent
     */
    public function setParent($parent = false)
    {
        $this->parent = $parent;
    }

    /**
     * @param string $class
     *
     * @return null|NodeMenuItem
     */
    public function getParentOfClass($class)
    {
        // Check for namespace alias
        if (false !== strpos($class, ':')) {
            [$namespaceAlias, $simpleClassName] = explode(':', $class);
            $class = $this->em->getConfiguration()->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;
        }
        if (null === $this->getParent()) {
            return null;
        }
        if ($this->parent->getPage() instanceof $class) {
            return $this->parent;
        }

        return $this->parent->getParentOfClass($class);
    }

    /**
     * @return NodeMenuItem[]
     */
    public function getParents()
    {
        $parent = $this->getParent();
        $parents = [];
        while (null !== $parent) {
            $parents[] = $parent;
            $parent = $parent->getParent();
        }

        return array_reverse($parents);
    }

    /**
     * @param bool $includeHiddenFromNav Include hiddenFromNav nodes
     *
     * @return NodeMenuItem[]
     */
    public function getChildren($includeHiddenFromNav = true)
    {
        if (null === $this->children) {
            $children = $this->menu->getChildren($this->node, true);
            // @var NodeMenuItem $child
            foreach ($children as $child) {
                $child->setParent($this);
            }
            $this->children = $children;
        }

        $children = array_filter($this->children, function (self $entry) use ($includeHiddenFromNav) {
            if ($entry->getNode()->isHiddenFromNav() && !$includeHiddenFromNav) {
                return false;
            }

            return true;
        });

        return $children;
    }

    /**
     * @param string $class
     *
     * @return NodeMenuItem[]
     */
    public function getChildrenOfClass($class)
    {
        // Check for namespace alias
        if (false !== strpos($class, ':')) {
            [$namespaceAlias, $simpleClassName] = explode(':', $class);
            $class = $this->em->getConfiguration()->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;
        }
        $result = [];
        $children = $this->getChildren();
        foreach ($children as $child) {
            if ($child->getPage() instanceof $class) {
                $result[] = $child;
            }
        }

        return $result;
    }

    /**
     * Get the first child of class, this is not using the getChildrenOfClass method for performance reasons.
     *
     * @param string $class
     *
     * @return NodeMenuItem
     */
    public function getChildOfClass($class)
    {
        // Check for namespace alias
        if (false !== strpos($class, ':')) {
            [$namespaceAlias, $simpleClassName] = explode(':', $class);
            $class = $this->em->getConfiguration()->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;
        }
        foreach ($this->getChildren() as $child) {
            if ($child->getPage() instanceof $class) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @return HasNodeInterface
     */
    public function getPage()
    {
        return $this->getNodeTranslation()->getPublicNodeVersion()->getRef($this->em);
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->menu->getActive($this->getSlug());
    }
}
