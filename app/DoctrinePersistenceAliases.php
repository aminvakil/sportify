<?php

if (interface_exists('Doctrine\\Persistence\\ObjectManager') && !interface_exists('Doctrine\\Common\\Persistence\\ObjectManager')) {
    class_alias('Doctrine\\Persistence\\ObjectManager', 'Doctrine\\Common\\Persistence\\ObjectManager');
}

if (interface_exists('Doctrine\\Persistence\\ObjectRepository') && !interface_exists('Doctrine\\Common\\Persistence\\ObjectRepository')) {
    class_alias('Doctrine\\Persistence\\ObjectRepository', 'Doctrine\\Common\\Persistence\\ObjectRepository');
}

if (interface_exists('Doctrine\\Persistence\\ManagerRegistry') && !interface_exists('Doctrine\\Common\\Persistence\\ManagerRegistry')) {
    class_alias('Doctrine\\Persistence\\ManagerRegistry', 'Doctrine\\Common\\Persistence\\ManagerRegistry');
}

if (class_exists('Doctrine\\Persistence\\Event\\LifecycleEventArgs') && !class_exists('Doctrine\\Common\\Persistence\\Event\\LifecycleEventArgs')) {
    class_alias('Doctrine\\Persistence\\Event\\LifecycleEventArgs', 'Doctrine\\Common\\Persistence\\Event\\LifecycleEventArgs');
}
