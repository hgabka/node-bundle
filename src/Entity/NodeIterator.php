<?php

namespace Hgabka\NodeBundle\Entity;

use Doctrine\Common\Collections\Collection;
use RecursiveIterator;

class NodeIterator implements RecursiveIterator
{
    private $_data;

    public function __construct(Collection $data)
    {
        $this->_data = $data;
    }

    public function hasChildren(): bool
    {
        return !$this->_data->current()->getChildren()->isEmpty();
    }

    public function getChildren(): ?RecursiveIterator
    {
        return new self($this->_data->current()->getChildren());
    }

    public function current(): mixed
    {
        return $this->_data->current();
    }

    public function next(): void
    {
        $this->_data->next();
    }

    public function key(): mixed
    {
        return $this->_data->key();
    }

    public function valid(): bool
    {
        return $this->_data->current() instanceof Node;
    }

    public function rewind(): void
    {
        $this->_data->first();
    }
}
