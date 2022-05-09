<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\NodeBundle\Form\NodeTranslationAdminType;
use Hgabka\NodeBundle\Repository\NodeTranslationRepository;
use Hgabka\UtilsBundle\Entity\EntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NodeTranslation.
 *
 * @ORM\Entity(repositoryClass="Hgabka\NodeBundle\Repository\NodeTranslationRepository")
 * @ORM\Table(
 *     name="hg_node_node_translations",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="ix_hg_node_translations_node_lang", columns={"node_id", "lang"})},
 *     indexes={@ORM\Index(name="idx__node_translation_lang_url", columns={"lang", "url"})}
 * )
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
#[ORM\Entity(repositoryClass: NodeTranslationRepository::class)]
#[ORM\Table(name: 'hg_node_node_translations')]
#[ORM\UniqueConstraint(name: 'ix_hg_node_translations_node_lang', columns: ['node_id', 'lang'])]
#[ORM\Index(name: 'idx__node_translation_lang_url', columns: ['lang', 'url'])]
class NodeTranslation implements EntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var Node
     *
     * @ORM\ManyToOne(targetEntity="Node", inversedBy="nodeTranslations")
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id")
     */
    #[ORM\ManyToOne(targetEntity: Node::class, inversedBy: 'nodeTranslations')]
    #[ORM\JoinColumn(name: 'node_id', referencedColumnName: 'id')]
    protected ?Node $node = null;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    #[ORM\Column(name: 'lang', type: 'string')]
    protected ?string $lang = null;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    #[ORM\Column(name: 'online', type: 'boolean')]
    protected bool $online = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    #[ORM\Column(name: 'title', type: 'string')]
    protected ?string $title = null;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Regex("/^[a-zA-Z0-9\-_\/]+$/")
     */
    #[ORM\Column(name: 'slug', type: 'string', nullable: true)]
    #[Assert\Regex('/^[a-zA-Z0-9\-_\/]+$/')]
    protected ?string $slug = null;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(name: 'url', type: 'string', nullable: true)]
    protected ?string $url = null;

    /**
     * @var NodeVersion
     *
     * @ORM\ManyToOne(targetEntity="NodeVersion", fetch="EAGER")
     * @ORM\JoinColumn(name="public_node_version_id", referencedColumnName="id")
     */
    #[ORM\ManyToOne(targetEntity: NodeVersion::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'public_node_version_id', referencedColumnName: 'id')]
    protected ?NodeVersion $publicNodeVersion = null;

    /**
     * @var ArrayCollection
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="NodeVersion", mappedBy="nodeTranslation")
     * @ORM\OrderBy({"created" = "ASC"})
     */
    #[ORM\OneToMany(targetEntity: NodeVersion::class, mappedBy: 'nodeTranslation')]
    #[ORM\OrderBy(['created', 'ASC'])]
    #[Assert\Valid]
    protected Collection|array|null $nodeVersions = null;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    #[ORM\Column(name: 'weight', type: 'smallint', nullable: true)]
    protected ?int $weight = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(name: 'created', type: 'datetime', nullable: true)]
    protected ?\DateTime $created = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(name: 'updated', type: 'datetime', nullable: true)]
    protected ?\DateTime $updated = null;

    /**
     * contructor.
     */
    public function __construct()
    {
        $this->nodeVersions = new ArrayCollection();
        $this->setCreated(new \DateTime());
        $this->setUpdated(new \DateTime());
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

    public function setNode(?Node $node): self
    {
        $this->node = $node;

        return $this;
    }

    public function getNode(): ?Node
    {
        return $this->node;
    }

    public function setLang(?string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function isOnline(): bool
    {
        return $this->online;
    }

    public function setOnline(bool $online): self
    {
        $this->online = $online;

        return $this;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getFullSlug(): ?string
    {
        $slug = $this->getSlugPart();

        if (empty($slug)) {
            return null;
        }

        return $slug;
    }

    public function getSlugPart(): ?string
    {
        $slug = '';
        $parentNode = $this->getNode()->getParent();
        if (null !== $parentNode) {
            $nodeTranslation = $parentNode->getNodeTranslation($this->lang, true);

            if (null !== $nodeTranslation) {
                $parentSlug = $nodeTranslation->getSlugPart();
                if (!empty($parentSlug)) {
                    $slug = rtrim($parentSlug, '/') . '/';
                }
            }
        }
        $slug = $slug . $this->getSlug();

        return $slug;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setPublicNodeVersion(?NodeVersion $publicNodeVersion): self
    {
        $this->publicNodeVersion = $publicNodeVersion;

        return $this;
    }

    public function getPublicNodeVersion(): ?NodeVersion
    {
        return $this->publicNodeVersion;
    }

    public function getDraftNodeVersion(): ?NodeVersion
    {
        return $this->getNodeVersion('draft');
    }

    public function getNodeVersions(): Collection|array|null
    {
        return $this->nodeVersions;
    }

    public function setNodeVersions(Collection|array|null $nodeVersions): self
    {
        $this->nodeVersions = $nodeVersions;

        return $this;
    }

    public function getNodeVersion(string $type): ?NodeVersion
    {
        if ('public' === $type) {
            return $this->publicNodeVersion;
        }

        $nodeVersions = $this->getNodeVersions();

        $max = \count($nodeVersions);
        for ($i = $max - 1; $i >= 0; --$i) {
            // @var NodeVersion $nodeVersion
            $nodeVersion = $nodeVersions[$i];

            if ($type === $nodeVersion->getType()) {
                return $nodeVersion;
            }
        }

        return null;
    }

    public function addNodeVersion(NodeVersion $nodeVersion): self
    {
        $this->nodeVersions[] = $nodeVersion;
        $nodeVersion->setNodeTranslation($this);

        if ('public' === $nodeVersion->getType()) {
            $this->publicNodeVersion = $nodeVersion;
        }

        return $this;
    }

    public function getDefaultAdminType(): string
    {
        return NodeTranslationAdminType::class;
    }

    /**
     * @param EntityManager $em   The entity manager
     * @param string        $type The type
     *
     * @return null|object
     */
    public function getRef(EntityManager $em, string $type = 'public'): mixed
    {
        $nodeVersion = $this->getNodeVersion($type);
        if ($nodeVersion) {
            return $em->getRepository($nodeVersion->getRefEntityName())->find($nodeVersion->getRefId());
        }

        return null;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setWeight(?int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setCreated(?\DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTime $updated): self
    {
        $this->updated = $updated;

        return $this;
    }
}
