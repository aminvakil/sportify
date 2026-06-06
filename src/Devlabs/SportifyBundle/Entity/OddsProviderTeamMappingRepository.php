<?php

namespace Devlabs\SportifyBundle\Entity;

class OddsProviderTeamMappingRepository extends \Doctrine\ORM\EntityRepository
{
    public function getByTournamentProviderAndNormalizedName($tournamentId, $provider, $normalizedProviderTeamName)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(OddsProviderTeamMapping::class, 'm')
            ->where('m.tournamentId = :tournament_id')
            ->andWhere('m.provider = :provider')
            ->andWhere('m.normalizedProviderTeamName = :normalized_provider_team_name')
            ->setParameter('tournament_id', $tournamentId)
            ->setParameter('provider', $provider)
            ->setParameter('normalized_provider_team_name', $normalizedProviderTeamName);

        try {
            return $query->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
