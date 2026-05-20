<?php

namespace Devlabs\SportifyBundle\DependencyInjection\Compiler;

use Devlabs\SportifyBundle\Bridge\FOSOAuthServer\AccessTokenManager;
use Devlabs\SportifyBundle\Bridge\FOSOAuthServer\AuthCodeManager;
use Devlabs\SportifyBundle\Bridge\FOSOAuthServer\ClientManager;
use Devlabs\SportifyBundle\Bridge\FOSOAuthServer\RefreshTokenManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LegacyDoctrinePersistencePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $classes = array(
            'fos_oauth_server.client_manager.default' => ClientManager::class,
            'fos_oauth_server.access_token_manager.default' => AccessTokenManager::class,
            'fos_oauth_server.refresh_token_manager.default' => RefreshTokenManager::class,
            'fos_oauth_server.auth_code_manager.default' => AuthCodeManager::class,
        );

        foreach ($classes as $serviceId => $class) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setClass($class);
            }
        }
    }
}
