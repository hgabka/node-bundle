<?php

namespace Hgabka\NodeBundle\Validation;

trait URLValidator
{
    /**
     * Check if given text is e-mail address.
     *
     */
    public function isEmailAddress($link): bool
    {
        return filter_var($link, \FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if given text is an internal link.
     *
     */
    public function isInternalLink(?string $link): bool
    {
        preg_match_all("/\[(([a-z_A-Z]+):)?NT([0-9]+)\]/", (string) $link, $matches, \PREG_SET_ORDER);

        return \count($matches) > 0;
    }

    /**
     * Check if given text is an internal media link.
     *
     */
    public function isInternalMediaLink(?string $link): bool
    {
        preg_match_all("/\[(([a-z_A-Z]+):)?M([0-9]+)\]/", (string) $link, $matches, \PREG_SET_ORDER);

        return \count($matches) > 0;
    }
}
