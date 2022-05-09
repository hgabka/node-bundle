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
#[ORM\Entity]
#[ORM\Table(name: 'hg_node_link_pages')]
class LinkPage extends AbstractPage implements SlugActionInterface
{
    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    #[ORM\Column(name: 'opens_in_new_window', type: 'boolean')]
    protected bool $opensInNewWindow = false;

    /**
     * @var null|string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[ORM\Column(type: string, length: 255, nullable: true)]
    private ?string $remoteUrl = null;

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
    public function getDefaultAdminType(): string
    {
        return LinkPageAdminType::class;
    }

    /**
     * @return array
     */
    public function getPossibleChildTypes(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getControllerAction(): string
    {
        return LinkPageController::class . ':service';
    }
}
