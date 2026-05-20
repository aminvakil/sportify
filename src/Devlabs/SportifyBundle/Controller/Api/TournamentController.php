<?php

namespace Devlabs\SportifyBundle\Controller\Api;

use Devlabs\SportifyBundle\Controller\Base\BaseApiController;
use Devlabs\SportifyBundle\Entity\Score;
use Devlabs\SportifyBundle\Entity\Tournament;
use Devlabs\SportifyBundle\Form\TournamentEntityType;

/**
 * Class TournamentController
 * @package Devlabs\SportifyBundle\Controller\Api
 */
class TournamentController extends BaseApiController
{
    protected $entityName = 'Tournament';
    protected $fqEntityClass = Tournament::class;
    protected $repositoryName = Tournament::class;
    protected $fqEntityFormClass = TournamentEntityType::class;

    /**
     * Get a tournament's scores/standings table
     *
     * @param $id
     * @return Response
     */
    public function getScoresAction($id)
    {
        $tournament = $this->getDoctrine()->getManager()
            ->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($tournament)) {
            return $this->getNotFoundView();
        }

        $scores = $this->getDoctrine()->getManager()
            ->getRepository(Score::class)
            ->getByTournamentOrderByPosNew($tournament);

        return $this->view($scores, 200);
    }

    /**
     * Get a tournament's matches
     *
     * @param $id
     * @return Response
     */
    public function getMatchesAction($id)
    {
        $object = $this->getDoctrine()->getManager()
            ->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($object)) {
            return $this->getNotFoundView();
        }

        return $this->view($object->getMatches(), 200);
    }

    /**
     * Get a tournament's teams
     *
     * @param $id
     * @return Response
     */
    public function getTeamsAction($id)
    {
        $object = $this->getDoctrine()->getManager()
            ->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($object)) {
            return $this->getNotFoundView();
        }

        return $this->view($object->getTeams(), 200);
    }

    /**
     * Get a tournament's user champion predictions
     *
     * @param $id
     * @return Response
     */
    public function getChamp_predictionsAction($id)
    {
        $object = $this->getDoctrine()->getManager()
            ->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($object)) {
            return $this->getNotFoundView();
        }

        return $this->view($object->getPredictionsChampion(), 200);
    }
}
