<?php

namespace Hgabka\NodeBundle\Form\Pages;

use Hgabka\NodeBundle\Form\PageAdminType;
use Hgabka\NodeBundle\Form\Type\URLChooserType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractFolderPageAdminType extends PageAdminType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('onlyStructure', CheckboxType::class, [
                'label' => 'hg_node.form.folder_type.only_structure',
                'required' => false,
            ])
            ->add('remoteUrl', URLChooserType::class, [
                'label' => 'hg_node.form.folder_type.remote_url',
                'required' => false,
                'link_types' => [
                    URLChooserType::EXTERNAL,
                    URLChooserType::INTERNAL,
                ],
            ])
        ;
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'folder_page';
    }
}
