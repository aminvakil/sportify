<?php

namespace Devlabs\SportifyBundle\Entity;

/**
 * Class OAuthAccessTokenRepository
 * @package Devlabs\SportifyBundle\Entity
 */
class OAuthAccessTokenRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Get the last not expired access token for the user
     *
     * @param User $user
     * @return array
     */
    public function getLastNotExpired(User $user)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('at')
            ->from(OAuthAccessToken::class, 'at')
            ->where('at.user = :user_id')
            ->andWhere('at.expiresAt > :current_timestamp')
            ->orderBy('at.expiresAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('user_id', $user->getId())
            ->setParameter('current_timestamp', time())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
