<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Node as GedmoNode;
use Hgabka\NodeBundle\Form\NodeAdminType;
use Hgabka\UtilsBundle\Entity\EntityInterface;
use Hgabka\UtilsBundle\Helper\ClassLookup;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Node.
 *
 * @ORM\Entity(repositoryClass="Hgabka\NodeBundle\Repository\NodeRepository")
 * @ORM\Table(
 *      name="hg_node_nodes",
 *      indexes={
 *          @ORM\Index(name="idx_node_internal_name", columns={"internal_name"}),
 *          @ORM\Index(name="idx_node_ref_entity_name", columns={"ref_entity_name"}),
 *          @ORM\Index(name="idx_node_tree", columns={"deleted", "hidden_from_nav", "lft", "rgt"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Gedmo\Tree(type="nested")
 */
class Node implements GedmoNode, EntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Node
     *
     * @ORM\ManyToOne(targetEntity="Node", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * @Gedmo\TreeParent
     */
    protected $parent;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Node", mappedBy="parent")
     */
    protected $children;

    /**
     * @var int
     *
     * @ORM\Column(name="lft", type="integer", nullable=true)
     * @Gedmo\TreeLeft
     */
    protected $lft;

    /**
     * @var int
     *
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     * @Gedmo\TreeLevel
     */
    protected $lvl;

    /**
     * @var int
     *
     * @ORM\Column(name="rgt", type="integer", nullable=true)
     * @Gedmo\TreeRight
     */
    protected $rgt;

    /**
     * @var ArrayCollection
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="NodeTranslation", mappedBy="node")
     */
    protected $nodeTranslations;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $deleted;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", name="hidden_from_nav")
     */
    protected $hiddenFromNav;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false, name="ref_entity_name")
     */
    protected $refEntityName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, name="internal_name")
     */
    protected $internalName;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->nodeTranslations = new ArrayCollection();
        $this->deleted = false;
        $this->hiddenFromNav = false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'node '.$this->getId().', refEntityName: '.$this->getRefEntityName();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return Node
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHiddenFromNav()
    {
        return $this->hiddenFromNav;
    }

    /**
     * @return bool
     */
    public function getHiddenFromNav()
    {
        return $this->hiddenFromNav;
    }

    /**
     * @param bool $hiddenFromNav
     *
     * @return Node
     */
    public function setHiddenFromNav($hiddenFromNav)
    {
        $this->hiddenFromNav = $hiddenFromNav;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children->filter(
            function (self $entry) {
                if ($entry->isDeleted()) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * @param mixed $sortFields
     *
     * @return ArrayCollection
     */
    public function getChildrenSorted($sortFields = ['lft' => 'ASC'])
    {
        return $this->getChildren()->matching(Criteria::create()->orderBy($sortFields));
    }

    /**
     * @param ArrayCollection $children
     *
     * @return Node
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Add children.
     *
     * @param Node $child
     *
     * @return Node
     */
    public function addNode(self $child)
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * @param bool $includeOffline
     *
     * @return ArrayCollection
     */
    public function getNodeTranslations($includeOffline = false)
    {
        return $this->nodeTranslations
            ->filter(
                function (NodeTranslation $entry) use ($includeOffline) {
                    if ($includeOffline || $entry->isOnline()) {
                        return true;
                    }

                    return false;
                }
            );
    }

    /**
     * @return Node
     */
    public function setNodeTranslations(ArrayCollection $nodeTranslations)
    {
        $this->nodeTranslations = $nodeTranslations;

        return $this;
    }

    /**
     * @param string $lang           The locale
     * @param bool   $includeOffline Include offline pages or not
     *
     * @return null|NodeTranslation
     */
    public function getNodeTranslation($lang, $includeOffline = false)
    {
        $nodeTranslations = $this->getNodeTranslations($includeOffline);
        // @var NodeTranslation $nodeTranslation
        foreach ($nodeTranslations as $nodeTranslation) {
            if ($lang === $nodeTranslation->getLang()) {
                return $nodeTranslation;
            }
        }

        return null;
    }

    /**
     * Add nodeTranslation.
     *
     * @return Node
     */
    public function addNodeTranslation(NodeTranslation $nodeTranslation)
    {
        $this->nodeTranslations[] = $nodeTranslation;
        $nodeTranslation->setNode($this);

        return $this;
    }

    /**
     * Set parent.
     *
     * @param Node $parent
     *
     * @return Node
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return Node
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return Node[]
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
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     *
     * @return Node
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Set referenced entity.
     *
     * @return Node
     */
    public function setRef(HasNodeInterface $entity)
    {
        $this->setRefEntityName(ClassLookup::getClass($entity));

        return $this;
    }

    /**
     * Get class name of referenced entity.
     *
     * @return string
     */
    public function getRefEntityName()
    {
        return $this->refEntityName;
    }

    /**
     * Set internal name.
     *
     * @param string $internalName
     *
     * @return Node
     */
    public function setInternalName($internalName)
    {
        $this->internalName = $internalName;

        return $this;
    }

    /**
     * Get internal name.
     *
     * @return string
     */
    public function getInternalName()
    {
        return $this->internalName;
    }

    /**
     * @return string
     */
    public function getDefaultAdminType()
    {
        return NodeAdminType::class;
    }

    /**
     * Get tree left.
     *
     * @return int
     */
    public function getLeft()
    {
        return $this->lft;
    }

    /**
     * Get tree right.
     *
     * @return int
     */
    public function getRight()
    {
        return $this->rgt;
    }

    /**
     * Get tree level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->lvl;
    }

    /**
     * Set class name of referenced entity.
     *
     * @param string $refEntityName
     *
     * @return Node
     */
    protected function setRefEntityName($refEntityName)
    {
        $this->refEntityName = $refEntityName;

        return $this;
    }
}
