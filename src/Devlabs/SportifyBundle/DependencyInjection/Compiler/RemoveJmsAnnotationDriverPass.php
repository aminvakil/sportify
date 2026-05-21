<?php

namespace Devlabs\SportifyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RemoveJmsAnnotationDriverPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('jms_serializer.metadata_driver')) {
            return;
        }

        $definition = $container->getDefinition('jms_serializer.metadata_driver');
        $drivers = array_filter(
            $definition->getArgument(0),
            static function ($driver) {
                return !$driver instanceof Reference || 'jms_serializer.metadata.annotation_or_attribute_driver' !== (string) $driver;
            }
        );

        $definition->replaceArgument(0, array_values($drivers));
    }
}
