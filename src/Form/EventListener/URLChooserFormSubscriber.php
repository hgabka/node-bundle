<?php

namespace Hgabka\NodeBundle\Form\EventListener;

use Hgabka\NodeBundle\Form\Type\URLChooserType;
use Hgabka\NodeBundle\Validation\URLValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class URLChooserFormSubscriber implements EventSubscriberInterface
{
    use URLValidator;

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
        ];
    }

    /**
     * When opening the form for the first time, check the type of URL and set the according fields.
     */
    public function postSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $form->getData();

        $attributes['class'] = 'js-change-urlchooser';

        if (!empty($data) && $form->has('link_type')) {
            // Check if e-mail address
            if ($this->isEmailAddress($data)) {
                $form->get('link_type')->setData(URLChooserType::EMAIL);
            } // Check if internal link
            elseif ($this->isInternalLink($data) || $this->isInternalMediaLink($data)) {
                $form->get('link_type')->setData(URLChooserType::INTERNAL);
                $attributes['choose_url'] = true;
            } // Else, it's an external link
            else {
                $form->get('link_type')->setData(URLChooserType::EXTERNAL);
            }
        } else {
            $choices = $form->get('link_type')->getConfig()->getOption('choices');
            $firstOption = array_shift($choices);

            if (URLChooserType::INTERNAL === $firstOption) {
                $attributes['choose_url'] = true;
            }

            $form->get('link_type')->setData($firstOption);
        }

        $form->add('link_url', TextType::class, [
            'label' => 'URL',
            'required' => true,
            'attr' => $attributes,
        ]);
    }
}
