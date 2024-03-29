<?php

namespace Hgabka\NodeBundle\Form;

use Hgabka\NodeBundle\Entity\NodeTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * NodeTranslationAdminType.
 */
class NodeTranslationAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('id', HiddenType::class);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'nodetranslation';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NodeTranslation::class,
        ]);
    }
}
