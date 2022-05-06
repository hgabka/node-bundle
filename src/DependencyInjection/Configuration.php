<?php

namespace Hgabka\NodeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('hgabka_node');
        $root = $treeBuilder->getRootNode();

        // @var ArrayNodeDefinition $pages
        $root
            ->children()
                ->arrayNode('pages')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('search_type')->end()
                            ->booleanNode('structure_node')->end()
                            ->booleanNode('indexable')->end()
                            ->scalarNode('icon')->defaultNull()->end()
                            ->scalarNode('hidden_from_tree')->end()
                            ->booleanNode('is_homepage')->end()
                            ->arrayNode('allowed_children')
                                ->prototype('array')
                                    ->beforeNormalization()
                                        ->ifString()->then(function ($v) { return ['class' => $v]; })
                                    ->end()
                                    ->children()
                                        ->scalarNode('class')->isRequired()->end()
                                        ->scalarNode('name')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('route')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('pattern')
                            ->cannotBeEmpty()
                            ->defaultValue(['default' => '/{url}'])
                            ->beforeNormalization()
                                ->ifString()->then(function ($v) { return ['default' => $v]; })
                            ->end()
                            ->useAttributeAsKey('name')
                                   ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('strategy')->cannotBeEmpty()->defaultValue('prefix_except_default')->end()
                    ->end()
                ->end()
                ->scalarNode('publish_later_stepping')->defaultValue('15')->end()
                ->scalarNode('unpublish_later_stepping')->defaultValue('15')->end()
                ->scalarNode('user_entity_class')->defaultValue('App\Entity\User')->end()
                ->booleanNode('show_add_homepage')->defaultTrue()->end()
                ->arrayNode('lock')
                    ->addDefaultsIfNotSet()
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('check_interval')->defaultValue(15)->end()
                        ->scalarNode('threshold')->defaultValue(35)->end()
                        ->booleanNode('enabled')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
