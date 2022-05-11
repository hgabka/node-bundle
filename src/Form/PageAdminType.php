<?php

namespace Hgabka\NodeBundle\Form;

use Hgabka\NodeBundle\Entity\AbstractPage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * PageAdminType.
 */
class PageAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('id', HiddenType::class);
        $builder->add('title', null, [
            'label' => 'hg_node.form.page.title.label',
        ]);
        $builder->add('pageTitle', null, [
            'label' => 'hg_node.form.page.page_title.label',
            'attr' => [
                'info_text' => 'hg_node.form.page.page_title.info_text',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
                'data_class' => AbstractPage::class,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'page';
    }
}
