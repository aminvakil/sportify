<?php

namespace Devlabs\SportifyBundle\Entity;

/**
 * Class ApiMappingRepository
 * @package Devlabs\SportifyBundle\Entity
 */
class ApiMappingRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Get a single ApiMapping object
     * by passing Entity type, API name and API Object ID
     *
     * @param $entityType
     * @param $apiName
     * @param $apiObjectId
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByEntityTypeAndApiObjectId($entityType, $apiName, $apiObjectId)
    {
        $query =  $this->getEntityManager()->createQueryBuilder()
            ->select('am')
            ->from(ApiMapping::class, 'am')
            ->where('am.entityType = :entity_type')
            ->andWhere('am.apiName = :api_name')
            ->andWhere('am.apiObjectId = :api_object_id')
            ->setParameter('entity_type', $entityType)
            ->setParameter('api_name', $apiName)
            ->setParameter('api_object_id', $apiObjectId);

        try {
            return $query->getQuery()->getSingleResult();
        }
        catch(\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get a single ApiMapping object
     * by passing Entity Object, Entity type and API name
     *
     * @param $entityObject
     * @param $entityType
     * @param $apiName
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByEntityAndApiProvider($entityObject, $entityType, $apiName)
    {
        $query =  $this->getEntityManager()->createQueryBuilder()
            ->select('am')
            ->from(ApiMapping::class, 'am')
            ->where('am.entityId = :entity_id')
            ->andWhere('am.entityType = :entity_type')
            ->andWhere('am.apiName = :api_name')
            ->setParameter('entity_id', $entityObject->getId())
            ->setParameter('entity_type', $entityType)
            ->setParameter('api_name', $apiName);

        try {
            return $query->getQuery()->getSingleResult();
        }
        catch(\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
