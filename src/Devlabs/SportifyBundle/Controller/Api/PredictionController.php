<?php

namespace Devlabs\SportifyBundle\Controller\Api;

use Devlabs\SportifyBundle\Controller\Base\BaseApiController;
use Symfony\Component\HttpFoundation\Request;
use Devlabs\SportifyBundle\Entity\User;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Form\PredictionType;

/**
 * Class PredictionController
 * @package Devlabs\SportifyBundle\Controller\Api
 */
class PredictionController extends BaseApiController
{
    protected $entityName = 'Prediction';
    protected $fqEntityClass = Prediction::class;
    protected $repositoryName = Prediction::class;
    protected $fqEntityFormClass = PredictionType::class;

    /**
     * Get all predictions for all users (ADMIN only)
     *
     * @param Request $request
     * @return Response
     */
    public function cgetAllusersAction(Request $request)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->view(null, 403);
        }

        // get an array of all the query string key-value pairs
        $params = $request->query->all();

        // get all user predictions (by passing in an 'empty' user object)
        $objects = $this->container->get('doctrine')->getManager()
            ->getRepository($this->repositoryName)
            ->findFiltered(new User(), $params);

        return $this->view($objects, 200);
    }

    /**
     * Get all predictions of requesting user
     *
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        // if user is not auth, return unauthorized
        if (!is_object($user = $this->getUser())) {
            return $this->getUnauthorizedView();
        }

        // get an array of all the query string key-value pairs
        $params = $request->query->all();

        // get user's predictions
        $objects = $this->container->get('doctrine')->getManager()
            ->getRepository($this->repositoryName)
            ->findFiltered($user, $params);

        return $this->view($objects, 200);
    }

    /**
     * Get a prediction by id
     *
     * @param $id
     * @return Response
     */
    public function getAction($id)
    {
        // if user is not logged in, return unauthorized
        if (!is_object($user = $this->getUser())) {
            return $this->getUnauthorizedView();
        }

        $object = $this->container->get('doctrine')->getManager()
            ->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($object)) {
            return $this->getNotFoundView();
        }

        // restrict normal user to be able to see only their data
        if (!$this->isGranted('ROLE_ADMIN') && $user != $object->getUserId()) {
            return $this->view(null, 403);
        }

        return $this->view($object, 200);
    }

    /**
     * Create a new prediction
     *
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request)
    {
        return parent::postAction($request);
    }

    /**
     * Modify or create a new prediction by given id
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        return parent::putAction($request, $id);
    }

    /**
     * Modify a prediction by id
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function patchAction(Request $request, $id)
    {
        return parent::patchAction($request, $id);
    }

    /**
     * Delete a prediction by id
     *
     * @param $id
     * @return Response
     */
    public function deleteAction($id)
    {
        return parent::deleteAction($id);
    }
}
