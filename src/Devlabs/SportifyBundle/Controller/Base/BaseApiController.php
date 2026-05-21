<?php

namespace Devlabs\SportifyBundle\Controller\Base;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ObjectManager;

/**
 * Class BaseApiController
 * @package Devlabs\SportifyBundle\Controller\Base
 */
abstract class BaseApiController extends AbstractController
{
    /**
     * The name of the model, e.g. 'Model'
     */
    protected $entityName;

    /**
     * The class of the model, e.g. Model::class
     */
    protected $fqEntityClass;

    /**
     * The repository for the model, e.g. Model::class
     */
    protected $repositoryName;

    /**
     * The form class for the model, e.g. ModelType::class
     */
    protected $fqEntityFormClass;

    /**
     * Get all resources of this type
     *
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        // if user is not logged in, return unauthorized
        if (!is_object($user = $this->getUser())) {
            return $this->getUnauthorizedView();
        }

        // get an array of all the query string key-value pairs
        $params = $request->query->all();

        $repository = $this->container->get('doctrine')->getManager()
            ->getRepository($this->repositoryName);

        $objects = (method_exists($repository, 'findFiltered'))
            ? $repository->findFiltered($params)
            : $repository->findAll();

        return $this->view($objects, 200);
    }

    /**
     * Get a resource by id
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

        $em = $this->container->get('doctrine')->getManager();

        $object = $em->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($object)) {
            return $this->getNotFoundView();
        }

        return $this->view($object, 200);
    }

    /**
     * Create a new resource of this type
     *
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->view(null, 403);
        }

        // if user is not logged in, return unauthorized
        if (!is_object($user = $this->getUser())) {
            return $this->getUnauthorizedView();
        }

        $em = $this->container->get('doctrine')->getManager();

        $object = new $this->fqEntityClass();

        return $this->processForm(
            $request,
            $em,
            $object,
            'POST',
            201
        );
    }

    /**
     * Modify or create a new resource of this type by given id
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->view(null, 403);
        }

        // if user is not logged in, return unauthorized
        if (!is_object($user = $this->getUser())) {
            return $this->getUnauthorizedView();
        }

        $em = $this->container->get('doctrine')->getManager();

        $object = $em->getRepository($this->repositoryName)
            ->findOneById($id);

        $statusCode = 204;

        if (!is_object($object)) {
            $object = new $this->fqEntityClass();
            $statusCode = 201;
        }

        return $this->processForm(
            $request,
            $em,
            $object,
            'PUT',
            $statusCode
        );
    }

    /**
     * Modify a resource of this type by id
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function patchAction(Request $request, $id)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->view(null, 403);
        }

        // if user is not logged in, return unauthorized
        if (!is_object($user = $this->getUser())) {
            return $this->getUnauthorizedView();
        }

        $em = $this->container->get('doctrine')->getManager();

        $object = $em->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($object)) {
            return $this->getNotFoundView();
        }

        return $this->processForm(
            $request,
            $em,
            $object,
            'PATCH',
            204
        );
    }

    /**
     * Delete a resource of this type by id
     *
     * @param $id
     * @return Response
     */
    public function deleteAction($id)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->view(null, 403);
        }

        // if user is not logged in, return unauthorized
        if (!is_object($user = $this->getUser())) {
            return $this->getUnauthorizedView();
        }

        $em = $this->container->get('doctrine')->getManager();

        $object = $em->getRepository($this->repositoryName)
            ->findOneById($id);

        if (!is_object($object)) {
            return $this->getNotFoundView();
        }

        // restrict normal user to be able to edit only their data
        if (method_exists($object, 'getUserId')
            && $user != $object->getUserId()
            && !$this->isGranted('ROLE_ADMIN'))
        {
            return $this->view(null, 403);
        }

        $em->remove($object);
        $em->flush();

        return $this->view(
            null,
            204
        );
    }

    /**
     * Create and process Entity form used for POST, PUT, PATCH requests
     *
     * @param Request $request
     * @param ObjectManager $em
     * @param $object
     * @param $method
     * @param int $statusCode
     * @return Response
     */
    protected function processForm(
        Request $request,
        ObjectManager $em,
        $object,
        $method,
        $statusCode = 200
    ) {
        // if user is not logged in, return unauthorized
        if (!is_object($user = $this->getUser())) {
            return $this->getUnauthorizedView();
        }

        $form = $this->createForm(
            $this->fqEntityFormClass,
            $object,
            array(
                'csrf_protection' => false,
                'method' => $method
            )
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            // restrict normal user to be able to edit only their data
            if (method_exists($object, 'getUserId')
                && $user != $object->getUserId()
                && !$this->isGranted('ROLE_ADMIN'))
            {
                return $this->view(null, 403);
            }

            $em->persist($object);
            $em->flush();

            return $this->view($object, $statusCode);
        }

        return $this->view($form, 400);
    }

    protected function view($data, $statusCode = 200)
    {
        $content = '';

        if (null !== $data && 204 !== $statusCode) {
            $content = $this->container->get('jms_serializer')->serialize($data, 'json');
        }

        return new Response($content, $statusCode, array('Content-Type' => 'application/json'));
    }

    /**
     * @return Response
     */
    protected function getUnauthorizedView()
    {
        return $this->view(null, 401);
    }

    /**
     * @return Response
     */
    protected function getNotFoundView()
    {
        return $this->view(null, 404);
    }
}