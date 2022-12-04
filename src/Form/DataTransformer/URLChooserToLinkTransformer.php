<?php

namespace Hgabka\NodeBundle\Form\DataTransformer;

use Hgabka\NodeBundle\Form\Type\URLChooserType;
use Hgabka\NodeBundle\Validation\URLValidator;
use Symfony\Component\Form\DataTransformerInterface;

class URLChooserToLinkTransformer implements DataTransformerInterface
{
    use URLValidator;

    public function transform($value): mixed
    {
        if ($this->isEmailAddress($value)) {
            $linkType = URLChooserType::EMAIL;
        } elseif ($this->isInternalLink($value) || $this->isInternalMediaLink($value)) {
            $linkType = URLChooserType::INTERNAL;
        } else {
            $linkType = URLChooserType::EXTERNAL;
        }

        return [
            'link_type' => $linkType,
            'link_url' => $value,
        ];
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (empty($value)) {
            return;
        }

        return $value['link_url'];
    }
}
