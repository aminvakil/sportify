<?php

namespace Symfony\Component\DependencyInjection;

trait ContainerAwareTrait
{
    protected $container;

    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }
}
