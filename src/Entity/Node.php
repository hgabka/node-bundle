<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Node as GedmoNode;
use Hgabka\NodeBundle\Form\NodeAdminType;
use Hgabka\NodeBundle\Repository\NodeRepository;
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
#[ORM\Entity(repositoryClass: NodeRepository::class)]
#[ORM\Table(name: 'hg_node_nodes')]
#[ORM\Index(name: 'idx_node_internal_name', columns: ['internal_name'])]
#[ORM\Index(name: 'idx_node_ref_entity_name', columns: ['ref_entity_name'])]
#[ORM\Index(name: 'idx_node_tree', columns: ['deleted', 'hidden_from_nav', 'lft', 'rgt'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[Gedmo\Tree(type: 'nested')]
class Node implements GedmoNode, EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    #[Gedmo\TreeParent]
    protected ?Node $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    protected Collection|array|null $children = null;

    #[ORM\Column(name: 'lft', type: 'integer', nullable: true)]
    #[Gedmo\TreeLeft]
    protected ?int $lft = null;

    #[ORM\Column(name: 'lvl', type: 'integer', nullable: true)]
    #[Gedmo\TreeLevel]
    protected ?int $lvl = null;

    #[ORM\Column(name: 'rgt', type: 'integer', nullable: true)]
    #[Gedmo\TreeRight]
    protected ?int $rgt = null;

    #[ORM\OneToMany(targetEntity: NodeTranslation::class, mappedBy: 'node')]
    #[Assert\Valid]
    protected Collection|array|null $nodeTranslations = null;

    #[ORM\Column(name: 'deleted', type: 'boolean')]
    protected bool $deleted = false;

    #[ORM\Column(name: 'hidden_from_nav', type: 'boolean')]
    protected bool $hiddenFromNav = false;

    #[ORM\Column(name: 'ref_entity_name', type: 'string', nullable: false)]
    protected ?string $refEntityName = null;

    #[ORM\Column(name: 'internal_name', type: 'string', nullable: true)]
    protected ?string $internalName = null;

    protected ?self $sibling = null;

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
    public function __toString(): string
    {
        return 'node ' . $this->getId() . ', refEntityName: ' . $this->getRefEntityName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHiddenFromNav(): bool
    {
        return $this->hiddenFromNav;
    }

    /**
     * @return bool
     */
    public function getHiddenFromNav(): bool
    {
        return $this->hiddenFromNav;
    }

    public function setHiddenFromNav(bool $hiddenFromNav): self
    {
        $this->hiddenFromNav = $hiddenFromNav;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren(): Collection|array|null
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
    public function getChildrenSorted($sortFields = ['lft' => 'ASC']): Collection|array|null
    {
        return $this->getChildren()->matching(Criteria::create()->orderBy($sortFields));
    }

    public function setChildren(Collection|array|null $children): self
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
    public function addNode(self $child): self
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
    public function getNodeTranslations(bool $includeOffline = false): Collection|array|null
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

    public function setNodeTranslations(Collection|array|null $nodeTranslations): self
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
    public function getNodeTranslation(string $lang, bool $includeOffline = false): ?NodeTranslation
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

    public function addNodeTranslation(NodeTranslation $nodeTranslation): self
    {
        $this->nodeTranslations[] = $nodeTranslation;
        $nodeTranslation->setNode($this);

        return $this;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @return Node[]
     */
    public function getParents(): ?array
    {
        $parent = $this->getParent();
        $parents = [];
        while (null !== $parent) {
            $parents[] = $parent;
            $parent = $parent->getParent();
        }

        return array_reverse($parents);
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function setRef(HasNodeInterface $entity): self
    {
        $this->setRefEntityName(ClassLookup::getClass($entity));

        return $this;
    }

    public function getRefEntityName(): ?string
    {
        return $this->refEntityName;
    }

    public function setInternalName(?string $internalName): self
    {
        $this->internalName = $internalName;

        return $this;
    }

    public function getInternalName(): ?string
    {
        return $this->internalName;
    }

    public function getDefaultAdminType(): string
    {
        return NodeAdminType::class;
    }

    public function getLeft(): ?int
    {
        return $this->lft;
    }

    public function getRight(): ?int
    {
        return $this->rgt;
    }

    public function getLevel(): ?int
    {
        return $this->lvl;
    }

    public function getSibling(): ?self
    {
        return $this->sibling;
    }

    public function setSibling(self $sibling): void
    {
        $this->sibling = $sibling;
    }

    protected function setRefEntityName(?string $refEntityName): self
    {
        $this->refEntityName = $refEntityName;

        return $this;
    }
}
