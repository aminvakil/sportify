<?php

namespace Devlabs\SportifyBundle\Bridge\FOSRest;

use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;

class JMSHandlerRegistry implements HandlerRegistryInterface
{
    private $registry;

    public function __construct(HandlerRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function registerSubscribingHandler(SubscribingHandlerInterface $handler): void
    {
        $this->registry->registerSubscribingHandler($handler);
    }

    public function registerHandler(int $direction, string $typeName, string $format, $handler): void
    {
        $this->registry->registerHandler($direction, $typeName, $format, $handler);
    }

    public function getHandler(int $direction, string $typeName, string $format)
    {
        do {
            $handler = $this->registry->getHandler($direction, $typeName, $format);
            if (null !== $handler) {
                return $handler;
            }
        } while ($typeName = get_parent_class($typeName));
    }
}
