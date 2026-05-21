<?php

namespace Devlabs\SportifyBundle\Controller\Api;

use Devlabs\SportifyBundle\Controller\Base\BaseApiController;
use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Form\MatchEntityType;

/**
 * Class MatchController
 * @package Devlabs\SportifyBundle\Controller\Api
 */
class MatchController extends BaseApiController
{
    protected $entityName = 'Match';
    protected $fqEntityClass = MatchEntity::class;
    protected $repositoryName = MatchEntity::class;
    protected $fqEntityFormClass = MatchEntityType::class;

    /**
     * Get all users' predictions for a match (ADMIN only)
     *
     * @return Response
     */
    public function getPredictionsAllusersAction($id)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->view(null, 403);
        }

        $match = $this->container->get('doctrine')->getManager()
            ->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($match)) {
            return $this->getNotFoundView();
        }

        return $this->view($match->getPredictions(), 200);
    }

    /**
     * Get the user's prediction for a match
     *
     * @param $id
     * @return mixed
     */
    public function getPredictionsAction($id)
    {
        // if user is not auth, return unauthorized
        if (!is_object($user = $this->getUser())) {
            return $this->getUnauthorizedView();
        }

        $prediction = $this->container->get('doctrine')->getManager()
            ->getRepository(Prediction::class)
            ->findOneBy(array(
                'matchId' => $id,
                'userId' => $user->getId()
            ));

        if (!is_object($prediction)) {
            return $this->getNotFoundView();
        }

        return $this->view($prediction, 200);
    }
}
