<?php

namespace Devlabs\SportifyBundle\Entity;


class ApiMapping
{
    private $id;

    private $entityId;

    private $entityType;

    private $apiName;

    private $apiObjectId;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param string $id
     *
     * @return ApiMapping
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     *
     * @return ApiMapping
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set entityType
     *
     * @param string $entityType
     *
     * @return ApiMapping
     */
    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;

        return $this;
    }

    /**
     * Get entityType
     *
     * @return string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * Set apiName
     *
     * @param string $apiName
     *
     * @return ApiMapping
     */
    public function setApiName($apiName)
    {
        $this->apiName = $apiName;

        return $this;
    }

    /**
     * Get apiName
     *
     * @return string
     */
    public function getApiName()
    {
        return $this->apiName;
    }

    /**
     * Set apiObjectId
     *
     * @param integer $apiObjectId
     *
     * @return ApiMapping
     */
    public function setApiObjectId($apiObjectId)
    {
        $this->apiObjectId = $apiObjectId;

        return $this;
    }

    /**
     * Get apiObjectId
     *
     * @return integer
     */
    public function getApiObjectId()
    {
        return $this->apiObjectId;
    }
}
