<?php

namespace Devlabs\SportifyBundle;

use Devlabs\SportifyBundle\DependencyInjection\Compiler\FOSRestCompatibilityPass;
use Devlabs\SportifyBundle\DependencyInjection\Compiler\PublicServicesPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DevlabsSportifyBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new PublicServicesPass());
        $container->addCompilerPass(new FOSRestCompatibilityPass(), PassConfig::TYPE_AFTER_REMOVING, -10);
    }
}
