<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Controller\RegistrationController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegistrationControllerTest extends TestCase
{
    public function testRegisterActionIsNotFoundWhenPublicRegistrationIsDisabled()
    {
        $container = new Container();
        $container->setParameter('app.public_registration_enabled', false);
        $container->set('parameter_bag', new ContainerBag($container));

        $controller = new RegistrationController();
        $controller->setContainer($container);

        $this->expectException(NotFoundHttpException::class);

        $controller->registerAction(new Request());
    }
}
