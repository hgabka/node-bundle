<?php

namespace Hgabka\NodeBundle\Entity;

use Hgabka\UtilsBundle\Entity\EntityInterface;

/**
 * HasNodeInterface Interface.
 */
interface HasNodeInterface extends EntityInterface
{
    /**
     * @return string
     */
    public function getTitle(): ?string;

    public function setTitle(?string $title): self;

    /**
     * @return HasNodeInterface
     */
    public function getParent(): ?self;

    /**
     * @param HasNodeInterface $hasNode
     */
    public function setParent(?self $hasNode): self;

    /**
     * @return string
     */
    public function getDefaultAdminType(): ?string;

    /**
     * @return array
     */
    public function getPossibleChildTypes(): array;

    /**
     * When this is true there won't be any save, publish, copy, menu, meta, preview, etc.
     * It's basically not a page. Just a node where other pages can hang under.
     *
     * @return bool
     */
    public function isStructureNode(): bool;
}
