<?php

namespace Hgabka\NodeBundle\Entity\Pages;

use Hgabka\NodeBundle\Entity\AbstractPage;
use Hgabka\NodeBundle\Form\PageAdminType;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\NodeBundle\Form\Pages\LinkPageAdminType;

/**
 * Link Page.
 *
 * @ORM\Entity()
 * @ORM\Table(name="hg_node_link_pages")
 */
class LinkPage extends AbstractPage
{

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $remoteUrl;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $opensInNewWindow = false;

    /**
     * @return string|null
     */
    public function getRemoteUrl(): ?string
    {
        return $this->remoteUrl;
    }

    /**
     * @param string|null $remoteUrl
     * @return LinkPage
     */
    public function setRemoteUrl(?string $remoteUrl): LinkPage
    {
        $this->remoteUrl = $remoteUrl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOpensInNewWindow(): bool
    {
        return $this->opensInNewWindow;
    }

    /**
     * @param bool $opensInNewWindow
     * @return LinkPage
     */
    public function setOpensInNewWindow(bool $opensInNewWindow): LinkPage
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
}