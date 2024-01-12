<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\NodeBundle\Form\PageAdminType;
use Hgabka\NodeBundle\Helper\RenderContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The Abstract ORM Page.
 */
abstract class AbstractPage implements PageInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank]
    protected ?string $title = null;

    #[ORM\Column(name: 'page_title', type: 'string', nullable: true)]
    protected ?string $pageTitle = null;

    /**
     * @var HasNodeInterface
     */
    protected ?HasNodeInterface $parent = null;

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getTitle();
    }

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
     * @return AbstractPage
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
     * @return AbstractPage
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
     * Set pagetitle.
     *
     * @param string $pageTitle
     *
     * @return AbstractPage
     */
    public function setPageTitle(?string $pageTitle): self
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    /**
     * Get pagetitle.
     *
     * @return string
     */
    public function getPageTitle(): ?string
    {
        if (!empty($this->pageTitle)) {
            return $this->pageTitle;
        }

        return $this->getTitle();
    }

    /**
     * @return HasNodeInterface
     */
    public function getParent(): ?HasNodeInterface
    {
        return $this->parent;
    }

    /**
     * @return AbstractPage
     */
    public function setParent(?HasNodeInterface $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns the default backend form type for this page.
     *
     * @return string
     */
    public function getDefaultAdminType(): string
    {
        return PageAdminType::class;
    }

    /**
     * @param ContainerInterface $container The Container
     * @param Request            $request   The Request
     * @param RenderContext      $context   The Render context
     *
     * @return RedirectResponse|void
     */
    public function service(ServiceLocator $container, Request $request, RenderContext $context)
    {
    }

    /**
     * By default this will return false. Pages will always be pages until some class says otherwise.
     *
     * {@inheritdoc}
     */
    public function isStructureNode(): bool
    {
        return false;
    }
}
