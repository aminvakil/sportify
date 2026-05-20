<?php

namespace Devlabs\SportifyBundle\Security;

use Devlabs\SportifyBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function loadUserByUsername($username)
    {
        $canonical = $this->canonicalize($username);
        $user = $this->em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.usernameCanonical = :username')
            ->orWhere('u.emailCanonical = :username')
            ->setParameter('username', $canonical)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('User "%s" was not found.', $username));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $refreshedUser = $this->em->getRepository(User::class)->find($user->getId());

        if (!$refreshedUser) {
            throw new UsernameNotFoundException(sprintf('User with id "%s" was not found.', $user->getId()));
        }

        return $refreshedUser;
    }

    public function supportsClass($class)
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    private function canonicalize($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }
}
