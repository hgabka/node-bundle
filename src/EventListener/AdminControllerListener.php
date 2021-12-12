<?php

namespace Hgabka\NodeBundle\EventListener;

use Hgabka\NodeBundle\Controller\NodeAdminController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminControllerListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => ['onKernelController', 10]];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (\is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof NodeAdminController) {
            $request = $event->getRequest();

            $request->query->set('_sonata_admin', 'hgabka_node.admin.node');
        }
    }
}
