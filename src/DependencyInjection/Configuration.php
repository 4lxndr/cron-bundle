<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('shapecode_cron');

        $treeBuilder->getRootNode()
            ->children()
                ->floatNode('timeout')
                    ->defaultNull()
                ->end()
                ->integerNode('result_retention_hours')
                    ->defaultNull()
                    ->info('Number of hours to retain CronJobResult records. Older results will be deleted automatically.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
