<?php

namespace Kunstmaan\NodeBundle\Entity;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\UtilsBundle\Helper\ClassLookup;

/**
 * NodeVersion.
 *
 * @ORM\Entity(repositoryClass="Hgabka\NodeBundle\Repository\NodeVersionRepository")
 * @ORM\Table(name="hg_node_node_versions", indexes={@ORM\Index(name="idx_node_version_lookup", columns={"ref_id", "ref_entity_name"})})
 * @ORM\HasLifecycleCallbacks()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class NodeVersion
{
    const DRAFT_VERSION = 'draft';
    const PUBLIC_VERSION = 'public';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var NodeTranslation
     *
     * @ORM\ManyToOne(targetEntity="NodeTranslation", inversedBy="nodeVersions")
     * @ORM\JoinColumn(name="node_translation_id", referencedColumnName="id")
     */
    protected $nodeTranslation;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $owner;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     * @var int
     *
     * @ORM\Column(type="bigint", name="ref_id")
     */
    protected $refId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="ref_entity_name")
     */
    protected $refEntityName;

    /**
     * The nodeVersion this nodeVersion originated from.
     *
     * @var NodeVersion
     *
     * @ORM\ManyToOne(targetEntity="NodeVersion")
     * @ORM\JoinColumn(name="origin_id", referencedColumnName="id")
     */
    protected $origin;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setCreated(new DateTime());
        $this->setUpdated(new DateTime());
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
     * @return NodeVersion
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set nodeTranslation.
     *
     * @param NodeTranslation $nodeTranslation
     *
     * @return NodeVersion
     */
    public function setNodeTranslation(NodeTranslation $nodeTranslation)
    {
        $this->nodeTranslation = $nodeTranslation;

        return $this;
    }

    /**
     * Get NodeTranslation.
     *
     * @return NodeTranslation
     */
    public function getNodeTranslation()
    {
        return $this->nodeTranslation;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function isDraft()
    {
        return self::DRAFT_VERSION === $this->type;
    }

    public function isPublic()
    {
        return self::PUBLIC_VERSION === $this->type;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return NodeVersion
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set owner.
     *
     * @param string $owner
     *
     * @return NodeVersion
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner.
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set created.
     *
     * @param DateTime $created
     *
     * @return NodeVersion
     */
    public function setCreated(DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param DateTime $updated
     *
     * @return NodeVersion
     */
    public function setUpdated(DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get refId.
     *
     * @return int
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * Get reference entity name.
     *
     * @return string
     */
    public function getRefEntityName()
    {
        return $this->refEntityName;
    }

    public function getDefaultAdminType()
    {
        return null;
    }

    /**
     * @param HasNodeInterface $entity
     *
     * @return NodeVersion
     */
    public function setRef(HasNodeInterface $entity)
    {
        $this->setRefId($entity->getId());
        $this->setRefEntityName(ClassLookup::getClass($entity));

        return $this;
    }

    /**
     * @param EntityManager $em
     *
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
