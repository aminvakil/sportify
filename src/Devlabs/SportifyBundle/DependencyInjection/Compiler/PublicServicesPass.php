<?php

namespace Devlabs\SportifyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PublicServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $serviceIds = array(
            'doctrine',
            'doctrine.dbal.default_connection',
            'doctrine.dbal.connection',
            'doctrine.orm.default_entity_manager',
            'doctrine.orm.entity_manager',
            'form.factory',
            'jms_serializer',
            'kernel',
            'mailer',
            'parameter_bag',
            'router',
            'security.authentication_utils',
            'security.authorization_checker',
            'security.csrf.token_manager',
            'security.user_password_hasher',
            'security.token_storage',
            'session',
            'twig',
            'validator',
        );

        foreach ($serviceIds as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setPublic(true);
            }

            if ($container->hasAlias($serviceId)) {
                $container->getAlias($serviceId)->setPublic(true);
            }
        }
    }
}
