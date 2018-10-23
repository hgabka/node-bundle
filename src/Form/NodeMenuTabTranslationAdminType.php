<?php

namespace Hgabka\NodeBundle\Form;

use Hgabka\NodeBundle\Entity\NodeTranslation;
use Hgabka\NodeBundle\Form\Type\SlugType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class NodeMenuTabTranslationAdminType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['slugable']) {
            $builder->add('slug', SlugType::class, [
                'label' => 'hg_node.form.menu_tab_translation.slug.label',
                'required' => false,
                'constraints' => [
                    new Regex("/^[a-zA-Z0-9\-_\/]+$/"),
                ],
            ]);
        }
        $builder->add('weight', ChoiceType::class, [
            'label' => 'hg_node.form.menu_tab_translation.weight.label',
            'choices' => array_combine(range(-50, 50), range(-50, 50)),
            'placeholder' => false,
            'required' => false,
            'attr' => ['title' => 'hg_node.form.menu_tab_translation.weight.title'],
            'choice_translation_domain' => false,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'menutranslation';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NodeTranslation::class,
            'slugable' => true,
        ]);
    }
}
