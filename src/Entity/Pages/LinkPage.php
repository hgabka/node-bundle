<?php

namespace Hgabka\NodeBundle\Entity\Pages;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\NodeBundle\Controller\LinkPageController;
use Hgabka\NodeBundle\Controller\SlugActionInterface;
use Hgabka\NodeBundle\Entity\AbstractPage;
use Hgabka\NodeBundle\Form\Pages\LinkPageAdminType;

/**
 * Link Page.
 *
 * @ORM\Entity()
 * @ORM\Table(name="hg_node_link_pages")
 */
class LinkPage extends AbstractPage implements SlugActionInterface
{
    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $opensInNewWindow = false;

    /**
     * @var null|string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $remoteUrl;

    public function getRemoteUrl(): ?string
    {
        return $this->remoteUrl;
    }

    public function setRemoteUrl(?string $remoteUrl): self
    {
        $this->remoteUrl = $remoteUrl;

        return $this;
    }

    public function isOpensInNewWindow(): bool
    {
        return $this->opensInNewWindow;
    }

    public function setOpensInNewWindow(bool $opensInNewWindow): self
    {
        $this->opensInNewWindow = $opensInNewWindow;

        return $this;
    }

    /**
     * Returns the default backend form type for this page.
     *
     * @return string
     */
    public function getDefaultAdminType()
    {
        return LinkPageAdminType::class;
    }

    /**
     * @return array
     */
    public function getPossibleChildTypes()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getControllerAction()
    {
        return LinkPageController::class . ':service';
    }
}
