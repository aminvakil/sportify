<?php

namespace Devlabs\SportifyBundle\Bridge\FOSOAuthServer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use FOS\OAuthServerBundle\Model\ClientInterface;
use FOS\OAuthServerBundle\Model\ClientManager as BaseClientManager;

class ClientManager extends BaseClientManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $class;

    public function __construct(ObjectManager $em, $class)
    {
        $this->em = $em;
        $this->repository = $em->getRepository($class);
        $this->class = $class;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function findClientBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    public function updateClient(ClientInterface $client)
    {
        $this->em->persist($client);
        $this->em->flush();
    }

    public function deleteClient(ClientInterface $client)
    {
        $this->em->remove($client);
        $this->em->flush();
    }
}
