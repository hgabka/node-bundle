<?php

namespace Hgabka\NodeBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Fixes bug with date vs Date headers.
 */
class FixDateListener
{
    /**
     * Make sure response has a timestamp.
     *
     * @param FilterResponseEvent|GetResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        if ($response) {
            $date = $response->getDate();
            if (empty($date)) {
                $response->setDate(new \DateTime());
            }
        }
    }
}
