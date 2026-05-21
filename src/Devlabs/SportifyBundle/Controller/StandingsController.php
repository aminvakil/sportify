<?php

namespace Devlabs\SportifyBundle\Controller;

use Devlabs\SportifyBundle\Entity\Score;
use Devlabs\SportifyBundle\Entity\Tournament;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Class StandingsController
 * @package Devlabs\SportifyBundle\Controller
 */
class StandingsController extends AbstractController
{
    public function indexAction(Request $request, $tournament_id)
    {
        $urlParams['tournament_id'] = $tournament_id;

        // Get an instance of the Entity Manager
        $em = $this->container->get('doctrine')->getManager();

        /**
         * Get selected tournament by last selected (from Cookie) and URL param is 'empty',
         * or set to first from DB if 'tournament' Cookie is not set and URL param is 'empty',
         * or get the tournament by the URL tournament_id value
         */
        if ($tournament_id === 'empty') {
            $formSourceData['tournament_selected'] = ($request->cookies->has('tournament'))
                ? $em->getRepository(Tournament::class)
                    ->findOneById($request->cookies->get('tournament'))
                : $em->getRepository(Tournament::class)
                    ->getFirst();
        } else {
            $formSourceData['tournament_selected'] = $em->getRepository(Tournament::class)
                ->findOneById($tournament_id);
        }

        /**
         * If expected data for 'tournament_selected' is not valid, get the first tournament.
         * (usually happens when invalid 'tournament id' is passed)
         */
        if (!$formSourceData['tournament_selected']) {
            $formSourceData['tournament_selected'] = $em->getRepository(Tournament::class)
                ->getFirst();
        }

        // get all tournaments as source data for form choices
        $formSourceData['tournament_choices'] = $em->getRepository(Tournament::class)
            ->findAll();

        // get the filter helper service
        $filterHelper = $this->container->get('app.filter.helper');

        // set the fields for the filter form
        $fields = array('tournament');

        // set the input data for the filter form and create it
        $formInputData = $filterHelper->getFormInputData($request, $urlParams, $fields, $formSourceData);
        $filterForm = $filterHelper->createForm($fields, $formInputData);
        $filterForm->handleRequest($request);

        // if the filter form is submitted, redirect with appropriate url path parameters
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $submittedParams = $filterHelper->actionOnFormSubmit($filterForm, $fields);

            return $this->redirectToRoute('standings_index', $submittedParams);
        }

        // init allScores and Response
        $allScores = array();
        $response = new Response();

        if ($formSourceData['tournament_selected']) {
            // get scores standings for a given tournament
            $allScores = $em->getRepository(Score::class)
                ->getByTournamentOrderByPosNew($formSourceData['tournament_selected']);

            // set cookie for tournament selected with 90-day expire period
            $response->headers->setCookie(new Cookie(
                'tournament',
                $formSourceData['tournament_selected']->getId(),
                time() + (3600 * 24 * 90)
            ));
        }

        // if user is logged in, get their standings and set them as global Twig var
        if (is_object($user = $this->getUser())) {
            $this->container->get('app.twig.helper')->setUserScores($user);
        }

        // rendering the view and returning the response
        return $this->render(
            'Standings/index.html.twig',
            array(
                'all_scores' => $allScores,
                'filter_form' => $filterForm->createView()
            ),
            $response
        );
    }

    public function index2Action(Request $request, $tournament_id)
    {
        $urlParams['tournament_id'] = $tournament_id;

        // Get an instance of the Entity Manager
        $em = $this->container->get('doctrine')->getManager();

        /**
         * Get selected tournament by last selected (from Cookie) and URL param is 'empty',
         * or set to first from DB if 'tournament' Cookie is not set and URL param is 'empty',
         * or get the tournament by the URL tournament_id value
         */
        if ($tournament_id === 'empty') {
            $formSourceData['tournament_selected'] = ($request->cookies->has('tournament'))
                ? $em->getRepository(Tournament::class)
                    ->findOneById($request->cookies->get('tournament'))
                : $em->getRepository(Tournament::class)
                    ->getFirst();
        } else {
            $formSourceData['tournament_selected'] = $em->getRepository(Tournament::class)
                ->findOneById($tournament_id);
        }

        /**
         * If expected data for 'tournament_selected' is not valid, get the first tournament.
         * (usually happens when invalid 'tournament id' is passed)
         */
        if (!$formSourceData['tournament_selected']) {
            $formSourceData['tournament_selected'] = $em->getRepository(Tournament::class)
                ->getFirst();
        }

        // get all tournaments as source data for form choices
        $formSourceData['tournament_choices'] = $em->getRepository(Tournament::class)
            ->findAll();

        // get the filter helper service
        $filterHelper = $this->container->get('app.filter.helper');

        // set the fields for the filter form
        $fields = array('tournament');

        // set the input data for the filter form and create it
        $formInputData = $filterHelper->getFormInputData($request, $urlParams, $fields, $formSourceData);
        $filterForm = $filterHelper->createForm($fields, $formInputData);
        $filterForm->handleRequest($request);

        // if the filter form is submitted, redirect with appropriate url path parameters
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $submittedParams = $filterHelper->actionOnFormSubmit($filterForm, $fields);

            return $this->redirectToRoute('standings_index', $submittedParams);
        }

        // init allScores and Response
        $allScores = array();
        $response = new Response();

        if ($formSourceData['tournament_selected']) {
            // get scores standings for a given tournament
            $allScores = $em->getRepository(Score::class)
                ->getByTournamentOrderByPosNew($formSourceData['tournament_selected']);

            // set cookie for tournament selected with 90-day expire period
            $response->headers->setCookie(new Cookie(
                'tournament',
                $formSourceData['tournament_selected']->getId(),
                time() + (3600 * 24 * 90)
            ));
        }

        // if user is logged in, get their standings and set them as global Twig var
        if (is_object($user = $this->getUser())) {
            $this->container->get('app.twig.helper')->setUserScores($user);
        }

        // rendering the view and returning the response
        return $this->render(
            'Standings/index2.html.twig',
            array(
                'all_scores' => $allScores,
                'filter_form' => $filterForm->createView()
            ),
            $response
        );
    }
}
