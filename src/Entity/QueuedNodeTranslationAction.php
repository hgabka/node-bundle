<?php

namespace Hgabka\NodeBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\UtilsBundle\Entity\EntityInterface;
use Hgabka\UtilsBundle\Model\AbstractUser;

#[ORM\Entity]
#[ORM\Table(name: 'hg_node_queued_node_translation_actions')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class QueuedNodeTranslationAction implements EntityInterface
{
    public const ACTION_PUBLISH = 'publish';
    public const ACTION_UNPUBLISH = 'unpublish';

    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NodeTranslation::class)]
    #[ORM\JoinColumn(name: 'node_translation_id', referencedColumnName: 'id')]
    protected ?NodeTranslation $nodeTranslation = null;

    #[ORM\Column(name: 'action', type: 'string')]
    protected ?string $action = null;

    protected ?AbstractUser $user = null;

    #[ORM\Column(name: '`date`', type: 'datetime')]
    protected ?DateTime $date = null;

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

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function setUser(?AbstractUser $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?AbstractUser
    {
        return $this->user;
    }

    public function setDate(?DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }
}
