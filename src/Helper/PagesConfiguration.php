<?php

namespace Hgabka\NodeBundle\Helper;

use // @noinspection PhpDeprecationInspection
    Hgabka\NodeBundle\Entity\HideFromNodeTreeInterface; // @noinspection PhpDeprecationInspection
use Hgabka\NodeBundle\Entity\HasNodeInterface;
use Hgabka\NodeBundle\Entity\HomePageInterface;
use Hgabka\SearchBundle\Helper\IndexableInterface;
use Hgabka\UtilsBundle\Helper\ClassLookup;

class PagesConfiguration
{
    private $configuration;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    public function getName($refName)
    {
        return $this->getValue($refName, 'name', substr($refName, strrpos($refName, '\\') + 1));
    }

    public function getIcon($refName)
    {
        return $this->getValue($refName, 'icon');
    }

    public function isHiddenFromTree($refName)
    {
        return $this->getValue($refName, 'hidden_from_tree', function ($page) {
            // @noinspection PhpDeprecationInspection
            return $page instanceof HideFromNodeTreeInterface;
        });
    }

    public function isIndexable($refName)
    {
        return $this->getValue($refName, 'indexable', function ($page) {
            // @var IndexableInterface $page
            return false === $page instanceof IndexableInterface || $page->isIndexable();
        });
    }

    public function getSearchType($refName)
    {
        return $this->getValue($refName, 'search_type', function ($page) {
            // @noinspection PhpDeprecationInspection
            return $page instanceof SearchTypeInterface ? $page->getSearchType() : ClassLookup::getClass($page);
        });
    }

    public function isStructureNode($refName)
    {
        return $this->getValue($refName, 'structure_node', function ($page) {
            // @noinspection PhpDeprecationInspection
            return $page instanceof HasNodeInterface && $page->isStructureNode();
        });
    }

    public function getPossibleChildTypes($refName)
    {
        $types = $this->getValue($refName, 'allowed_children', function ($page) {
            // @noinspection PhpDeprecationInspection
            return ($page instanceof HasNodeInterface) ? $page->getPossibleChildTypes() : [];
        });

        return array_map(function ($type) {
            return $type + ['name' => $this->getName($type['class'])]; // add if not set
        }, $types);
    }

    public function isHomePage($refName)
    {
        return $this->getValue($refName, 'is_homepage', function ($page) {
            // @noinspection PhpDeprecationInspection
            return $page instanceof HomePageInterface;
        });
    }

    public function getHomepageTypes()
    {
        $pageTypes = array_keys($this->configuration);
        $homePageTypes = [];
        foreach ($pageTypes as $pageType) {
            if ($this->isHomePage($pageType)) {
                $homePageTypes[$pageType] = $this->getName($pageType);
            }
        }

        return $homePageTypes;
    }

    private function getValue(mixed $ref, ?string $name, mixed $default = null): mixed
    {
        $refName = \is_object($ref) ? ClassLookup::getClass($ref) : $ref;

        if (isset($this->configuration[$refName][$name])) {
            return $this->configuration[$refName][$name];
        }

        if (false === \is_callable($default)) {
            return $default;
        }

        $page = \is_string($ref) && class_exists($refName) ? new $refName() : $ref;

        $result = $default($page);
        unset($page);
        $this->configuration[$refName][$name] = $result;

        return $result;
    }
}
