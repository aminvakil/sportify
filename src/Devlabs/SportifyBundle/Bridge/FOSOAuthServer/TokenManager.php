<?php

namespace Devlabs\SportifyBundle\Bridge\FOSOAuthServer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use FOS\OAuthServerBundle\Model\TokenInterface;
use FOS\OAuthServerBundle\Model\TokenManager as BaseTokenManager;

class TokenManager extends BaseTokenManager
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

    public function findTokenBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    public function updateToken(TokenInterface $token)
    {
        $this->em->persist($token);
        $this->em->flush();
    }

    public function deleteToken(TokenInterface $token)
    {
        $this->em->remove($token);
        $this->em->flush();
    }

    public function deleteExpired()
    {
        $qb = $this->repository->createQueryBuilder('t');
        $qb
            ->delete()
            ->where('t.expiresAt < ?1')
            ->setParameters(array(1 => time()));

        return $qb->getQuery()->execute();
    }
}
