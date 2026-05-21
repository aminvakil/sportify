<?php

namespace Symfony\Component\DependencyInjection;

interface ContainerAwareInterface
{
    public function setContainer(?ContainerInterface $container = null): void;
}
