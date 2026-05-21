<?php

namespace Devlabs\SportifyBundle\Controller\Api;

use Devlabs\SportifyBundle\Controller\Base\BaseApiController;
use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Form\TeamEntityType;

/**
 * Class TeamController
 * @package Devlabs\SportifyBundle\Controller\Api
 */
class TeamController extends BaseApiController
{
    protected $entityName = 'Team';
    protected $fqEntityClass = Team::class;
    protected $repositoryName = Team::class;
    protected $fqEntityFormClass = TeamEntityType::class;

    /**
     * Get a team's tournaments
     *
     * @param $id
     * @return mixed
     */
    public function getTournamentsAction($id)
    {
        $object = $this->container->get('doctrine')->getManager()
            ->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($object)) {
            return $this->getNotFoundView();
        }

        return $this->view($object->getTournaments(), 200);
    }
}
