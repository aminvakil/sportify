<?php

namespace Devlabs\SportifyBundle\DependencyInjection\Compiler;

use Devlabs\SportifyBundle\Bridge\FOSRest\JMSHandlerRegistry;
use Devlabs\SportifyBundle\Bridge\FOSRest\JMSSerializerAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FOSRestCompatibilityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach (array('jms_serializer.handler_registry', 'fos_rest.serializer.jms_handler_registry') as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setClass(JMSHandlerRegistry::class);
            }
        }

        if ($container->hasDefinition('fos_rest.serializer.jms')) {
            $container->getDefinition('fos_rest.serializer.jms')->setClass(JMSSerializerAdapter::class);
        }
    }
}
