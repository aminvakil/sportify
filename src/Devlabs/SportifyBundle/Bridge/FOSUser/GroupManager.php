<?php

namespace Devlabs\SportifyBundle\Bridge\FOSUser;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use FOS\UserBundle\Model\GroupInterface;
use FOS\UserBundle\Model\GroupManager as BaseGroupManager;

class GroupManager extends BaseGroupManager
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var ObjectRepository
     */
    protected $repository;

    public function __construct(ObjectManager $om, $class)
    {
        $this->objectManager = $om;
        $this->repository = $om->getRepository($class);

        $metadata = $om->getClassMetadata($class);
        $this->class = $metadata->getName();
    }

    public function deleteGroup(GroupInterface $group)
    {
        $this->objectManager->remove($group);
        $this->objectManager->flush();
    }

    public function getClass()
    {
        return $this->class;
    }

    public function findGroupBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    public function findGroups()
    {
        return $this->repository->findAll();
    }

    public function updateGroup(GroupInterface $group, $andFlush = true)
    {
        $this->objectManager->persist($group);
        if ($andFlush) {
            $this->objectManager->flush();
        }
    }
}
