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
        $request = $event->getRequest();
        $availableLocales = $this->utils->getAvailableLocales();
        $nodeLocale = $request->getLocale();
        if (\count($availableLocales) > 1) {
            if ($request->query->has('nodeLocale')) {
                $nodeLocale = $request->query->get('nodeLocale');
            } elseif ($this->session->has('nodeLocale')) {
                $nodeLocale = $this->session->get('nodeLocale');
            }

            if (empty($nodeLocale) || !\in_array($nodeLocale, $availableLocales, true)) {
                $nodeLocale = $request->getLocale();
            }

            $this->session->set('nodeLocale', $nodeLocale);
        }

        $request->attributes->set('nodeLocale', $nodeLocale);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 50]],
        ];
    }
}
