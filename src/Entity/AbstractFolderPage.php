<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\NodeBundle\Controller\FolderPageController;
use Hgabka\NodeBundle\Controller\SlugActionInterface;

abstract class AbstractFolderPage extends AbstractPage implements SlugActionInterface
{
    #[ORM\Column(name: 'remote_url', type: 'string', length: 255, nullable: true)]
    protected ?string $remoteUrl = null;

    #[ORM\Column(name: 'only_structure', type: 'boolean', nullable: true)]
    protected ?bool $onlyStructure = true;

    /**
     * @return null|string
     */
    public function getRemoteUrl(): ?string
    {
        return $this->remoteUrl;
    }

    /**
     * @param null|string $remoteUrl
     *
     * @return AbstractFolderPage
     */
    public function setRemoteUrl(?string $remoteUrl): self
    {
        $this->remoteUrl = $remoteUrl;

        return $this;
    }

    /**
     * @return null|bool
     */
    public function isOnlyStructure(): ?bool
    {
        return $this->onlyStructure;
    }

    /**
     * @param null|bool $onlyStructure
     *
     * @return AbstractFolderPage
     */
    public function setOnlyStructure(?bool $onlyStructure): self
    {
        $this->onlyStructure = $onlyStructure;

        return $this;
    }

    public function isStructureNode(): bool
    {
        return (bool) $this->isOnlyStructure();
    }

    public function getControllerAction(): string
    {
        return FolderPageController::class;
    }
}
