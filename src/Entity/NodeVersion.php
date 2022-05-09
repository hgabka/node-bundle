<?php

namespace Hgabka\NodeBundle\Entity;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\NodeBundle\Repository\NodeVersionRepository;
use Hgabka\UtilsBundle\Entity\EntityInterface;
use Hgabka\UtilsBundle\Helper\ClassLookup;

#[ORM\Entity(repositoryClass: NodeVersionRepository::class)]
#[ORM\Table(name: 'hg_node_node_versions')]
#[ORM\Index(name: 'idx_node_version_lookup', columns: ['ref_id', 'ref_entity_name'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class NodeVersion implements EntityInterface
{
    public const DRAFT_VERSION = 'draft';
    public const PUBLIC_VERSION = 'public';

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NodeTranslation::class, inversedBy: 'nodeVersions')]
    #[ORM\JoinColumn(name: 'node_translation_id', referencedColumnName: 'id')]
    protected ?NodeTranslation $nodeTranslation = null;

    #[ORM\Column(name: 'type', type: 'string')]
    protected ?string $type = null;

    #[ORM\Column(name: 'owner', type: 'string')]
    protected ?string $owner = null;

    #[ORM\Column(name: 'created', type: 'datetime')]
    protected ?DateTime $created = null;

    #[ORM\Column(name: 'updated', type: 'datetime')]
    protected ?DateTime $updated = null;

    #[ORM\Column(name: 'ref_id', type: 'bigint')]
    protected ?int $refId = null;

    #[ORM\Column(name: 'ref_entity_name', type: 'string')]
    protected ?string $refEntityName = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'origin_id', referencedColumnName: 'id')]
    protected ?NodeVersion $origin = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setCreated(new DateTime());
        $this->setUpdated(new DateTime());
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

    public function setNodeTranslation(?NodeTranslation $nodeTranslation): self
    {
        $this->nodeTranslation = $nodeTranslation;

        return $this;
    }

    public function getNodeTranslation(): ?NodeTranslation
    {
        return $this->nodeTranslation;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function isDraft(): bool
    {
        return self::DRAFT_VERSION === $this->type;
    }

    public function isPublic(): bool
    {
        return self::PUBLIC_VERSION === $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setOwner(?string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function setCreated(?DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setUpdated(DateTime $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    public function getRefId(): ?int
    {
        return $this->refId;
    }

    public function getRefEntityName(): ?string
    {
        return $this->refEntityName;
    }

    public function getDefaultAdminType(): ?string
    {
        return null;
    }

    /**
     * @return NodeVersion
     */
    public function setRef(HasNodeInterface $entity)
    {
        $this->setRefId($entity->getId());
        $this->setRefEntityName(ClassLookup::getClass($entity));

        return $this;
    }

    /**
     * @return HasNodeInterface
     */
    public function getRef(EntityManager $em)
    {
        return $em->getRepository($this->getRefEntityName())->find($this->getRefId());
    }

    /**
     * @param NodeVersion $origin
     *
     * @return NodeVersion
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return NodeVersion
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Set refId.
     *
     * @param int $refId
     *
     * @return NodeVersion
     */
    protected function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * Set reference entity name.
     *
     * @param string $refEntityName
     *
     * @return NodeVersion
     */
    protected function setRefEntityName($refEntityName)
    {
        $this->refEntityName = $refEntityName;

        return $this;
    }
}
