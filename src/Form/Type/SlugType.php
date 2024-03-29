<?php

namespace Hgabka\NodeBundle\Form\Type;

use Hgabka\UtilsBundle\Helper\SlugifierInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Sype.
 */
class SlugType extends AbstractType
{
    private SlugifierInterface $slugifier;

    public function __construct(SlugifierInterface $slugifier)
    {
        $this->slugifier = $slugifier;
    }

    /**
     * @return string
     */
    public function getParent(): ?string
    {
        return TextType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'slug';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $nodeTranslation = $form->getParent()->getData();
        $view->vars['reset'] = $this->slugifier->slugify($nodeTranslation->getTitle(), '');
        $parentNode = $nodeTranslation->getNode()->getParent();
        if (null !== $parentNode) {
            $nodeTranslation = $parentNode->getNodeTranslation($nodeTranslation->getLang(), true);
            $slug = $nodeTranslation->getSlugPart();
            if (!empty($slug)) {
                $slug .= '/';
            }
            $view->vars['prefix'] = $slug;
        }
    }
}
