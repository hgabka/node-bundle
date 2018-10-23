<?php

namespace Hgabka\NodeBundle\Validation;

trait URLValidator
{
    /**
     * Check if given text is e-mail address.
     *
     * @param mixed $link
     */
    public function isEmailAddress($link)
    {
        return filter_var($link, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if given text is an internal link.
     *
     * @param mixed $link
     */
    public function isInternalLink($link)
    {
        preg_match_all("/\[(([a-z_A-Z]+):)?NT([0-9]+)\]/", $link, $matches, PREG_SET_ORDER);

        return count($matches) > 0;
    }

    /**
     * Check if given text is an internal media link.
     *
     * @param mixed $link
     */
    public function isInternalMediaLink($link)
    {
        preg_match_all("/\[(([a-z_A-Z]+):)?M([0-9]+)\]/", $link, $matches, PREG_SET_ORDER);

        return count($matches) > 0;
    }
}
