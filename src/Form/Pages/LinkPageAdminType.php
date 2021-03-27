<?php

namespace Hgabka\NodeBundle\Form\Pages;

use Hgabka\NodeBundle\Entity\Pages\LinkPage;
use Hgabka\NodeBundle\Form\PageAdminType;
use Hgabka\NodeBundle\Form\Type\URLChooserType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LinkPageAdminType extends PageAdminType
{
    /**
     * Builds the form.
     *
     * This method is called for each type in the hierarchy starting form the
     * top most type. Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     *
     * @SuppressWarnings("unused")
     *
     * @see FormTypeExtensionInterface::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('remoteUrl', URLChooserType::class, [
                'label' => 'Cím',
                'required' => true,
                'constraints' => new NotBlank(),
                'link_types' => [
                    URLChooserType::EXTERNAL,
                    URLChooserType::INTERNAL,
                ],
            ])
            ->add('opensInNewWindow', CheckboxType::class, [
                'label' => 'Új fülön nyílik meg',
                'required' => false,
            ])
        ;
    }

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolver $resolver the resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LinkPage::class,
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'link_page';
    }
}
