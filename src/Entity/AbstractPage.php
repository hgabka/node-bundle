<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\NodeBundle\Form\PageAdminType;
use Hgabka\NodeBundle\Helper\RenderContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The Abstract ORM Page.
 */
abstract class AbstractPage implements PageInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, name="page_title")
     */
    protected $pageTitle;

    /**
     * @var HasNodeInterface
     */
    protected $parent;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getTitle();
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
     * @return AbstractPage
     */
    public function setId($id)
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
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
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
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    /**
     * Get pagetitle.
     *
     * @return string
     */
    public function getPageTitle()
    {
        if (!empty($this->pageTitle)) {
            return $this->pageTitle;
        }

        return $this->getTitle();
    }

    /**
     * @return HasNodeInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param HasNodeInterface $parent
     *
     * @return AbstractPage
     */
    public function setParent(HasNodeInterface $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns the default backend form type for this page.
     *
     * @return string
     */
    public function getDefaultAdminType()
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
    public function service(ContainerInterface $container, Request $request, RenderContext $context)
    {
    }

    /**
     * By default this will return false. Pages will always be pages until some class says otherwise.
     *
     * {@inheritdoc}
     */
    public function isStructureNode()
    {
        return false;
    }
}
