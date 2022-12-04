<?php

namespace Hgabka\NodeBundle\EventListener;

use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

class NodeLocaleListener implements EventSubscriberInterface
{
    public function __construct(private readonly HgabkaUtils $utils) {}

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $availableLocales = $this->utils->getAvailableLocales();
        $nodeLocale = $request->getLocale();
        if (count($availableLocales) > 1) {
            try {
                $session = $request->getSession();
            } catch (Throwable) {
                $session = null;
            }

            if ($request->query->has('nodeLocale')) {
                $nodeLocale = $request->query->get('nodeLocale');
            } elseif ($session && $session->has('nodeLocale')) {
                $nodeLocale = $request->getSession()->get('nodeLocale');
            }

            if (empty($nodeLocale) || !in_array($nodeLocale, $availableLocales, true)) {
                $nodeLocale = $request->getLocale();
            }

            if ($session) {
                $session->set('nodeLocale', $nodeLocale);
            }
        }

        $request->attributes->set('nodeLocale', $nodeLocale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 50]],
        ];
    }
}
