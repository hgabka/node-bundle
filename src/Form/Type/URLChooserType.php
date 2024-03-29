<?php

namespace Hgabka\NodeBundle\Form\Type;

use Hgabka\NodeBundle\Form\DataTransformer\URLChooserToLinkTransformer;
use Hgabka\NodeBundle\Form\EventListener\URLChooserFormSubscriber;
use Hgabka\NodeBundle\Form\EventListener\URLChooserLinkTypeSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * URLChooserType.
 */
class URLChooserType extends AbstractType
{
    public const INTERNAL = 'internal';
    public const EXTERNAL = 'external';
    public const EMAIL = 'email';

    /**
     * Builds the form.
     *
     * This method is called for each type in the hierarchy starting form the
     * top most type. Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     *
     * @see FormTypeExtensionInterface::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [
            'kuma_admin.pagepart.link.internal' => self::INTERNAL,
            'kuma_admin.pagepart.link.external' => self::EXTERNAL,
            'kuma_admin.pagepart.link.email' => self::EMAIL,
        ];

        if ($types = $options['link_types']) {
            foreach ($choices as $key => $choice) {
                if (!\in_array($choice, $types, true)) {
                    unset($choices[$key]);
                }
            }
        }

        $builder->add('link_type', ChoiceType::class, [
            'required' => true,
            'mapped' => false,
            'attr' => [
                'class' => 'js-change-link-type',
            ],
            'choices' => $choices,
        ]);

        $builder->get('link_type')->addEventSubscriber(new URLChooserLinkTypeSubscriber());

        $builder->addEventSubscriber(new URLChooserFormSubscriber());
        $builder->addViewTransformer(new URLChooserToLinkTransformer());
    }

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolver $resolver the resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'link_types' => [],
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'urlchooser';
    }
}
