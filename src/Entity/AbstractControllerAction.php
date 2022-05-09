<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\NodeBundle\Form\ControllerActionAdminType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AbstractControllerAction.
 */
abstract class AbstractControllerAction implements HasNodeInterface
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
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    #[ORM\Column(name: 'title', type: 'string')]
    #[Assert\NotBlank]
    protected ?string $title = null;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, name="page_title")
     */
    #[ORM\Column(name: 'page_title', type: 'string', nullable: true)]
    protected ?string $pageTitle = null;

    /**
     * @var HasNodeInterface
     */
    protected ?HasNodeInterface $parent = null;

    /**
     * @return mixed
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return AbstractControllerAction
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return AbstractControllerAction
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return HasNodeInterface
     */
    public function getParent(): ?HasNodeInterface
    {
        return $this->parent;
    }

    /**
     * @return AbstractControllerAction
     */
    public function setParent(?HasNodeInterface $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultAdminType(): string
    {
        return ControllerActionAdminType::class;
    }
}
