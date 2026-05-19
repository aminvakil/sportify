<?php

namespace Devlabs\SportifyBundle;

use Devlabs\SportifyBundle\DependencyInjection\Compiler\LegacyDoctrinePersistencePass;
use Devlabs\SportifyBundle\DependencyInjection\Compiler\PublicServicesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DevlabsSportifyBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new PublicServicesPass());
        $container->addCompilerPass(new LegacyDoctrinePersistencePass());
    }
}
