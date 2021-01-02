<?php

namespace Hgabka\NodeBundle\Twig;

use Hgabka\NodeBundle\Helper\URLHelper;

class UrlReplaceTwigExtension extends \Twig_Extension
{
    /**
     * @var URLHelper
     */
    private $urlHelper;

    public function __construct(URLHelper $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('replace_url', [$this, 'replaceUrl']),
        ];
    }

    public function replaceUrl($text)
    {
        return $this->urlHelper->replaceUrl($text);
    }
}
