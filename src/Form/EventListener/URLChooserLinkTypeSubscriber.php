<?php

namespace Hgabka\NodeBundle\Form\EventListener;

use Hgabka\NodeBundle\Form\Type\URLChooserType;
use Hgabka\NodeBundle\Validation\URLValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class URLChooserLinkTypeSubscriber implements EventSubscriberInterface
{
    use URLValidator;

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    /**
     * When changing the link type, the form get's submitted with an ajax callback in the url_chooser.js;
     * We add the URL field only as an URL Chooser if it's an external link.
     */
    public function postSubmit(FormEvent $event)
    {
        // Suppress validation
        $event->stopPropagation();

        $attributes['class'] = 'js-change-urlchooser';

        $form = $event->getForm()->getParent();
        $linkType = $event->getData();

        if ($linkType) {
            $form->remove('link_url');

            switch ($linkType) {
                case URLChooserType::INTERNAL:
                    $attributes['choose_url'] = true;

                    break;
            }

            $form->add('link_url', TextType::class, [
                'label' => 'URL',
                'required' => true,
                'attr' => $attributes,
            ]);
        }
    }
}
