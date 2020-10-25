<?php

namespace Hgabka\NodeBundle\EventListener;

use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class NodeLocaleListener implements EventSubscriberInterface
{
    /** @var SessionInterface */
    private $session;

    /** @var HgabkaUtils */
    private $utils;

    /**
     * @param string           $defaultLocale The default locale
     * @param RouterInterface  $router        The router
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session, HgabkaUtils $hgabkaUtils)
    {
        $this->session = $session;
        $this->utils = $hgabkaUtils;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->utils->getAvailableLocales() > 1) {
            $request = $event->getRequest();
            if ($request->query->has('nodeLocale')) {
                $nodeLocale = $request->query->get('nodeLocale');
                $this->session->set('nodeLocale', $nodeLocale);
            } elseif ($this->session->has('nodeLocale')) {
                $nodeLocale = $this->session->get('nodeLocale');
            } else {
                $nodeLocale = $request->getLocale();
            }

            $request->attributes->set('nodeLocale', $nodeLocale);
        } else {
            $request->attributes->set('nodeLocale', $request->getLocale());
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 50]],
        ];
    }
}
