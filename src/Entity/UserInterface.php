<?php

namespace Hgabka\NodeBundle\Entity;

interface UserInterface
{
    public function getId(): ?int;

    public function setId(?int $id): self;
}
