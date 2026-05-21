<?php

namespace Devlabs\SportifyBundle\Controller;

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HistoryController
 * @package Devlabs\SportifyBundle\Controller
 */
class HistoryController extends AbstractController
{
    public function indexAction(Request $request, $user_id, $tournament_id, $date_from, $date_to)
    {
        // if user is not logged in, redirect to login page
        if (!is_object($user = $this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        // get the matches helper service
        $historyHelper = $this->container->get('app.history.helper');
        $historyHelper->setCurrentUser($user);

        // set default values to route parameters if they are 'empty'
        $urlParams = $historyHelper->initUrlParams($user_id, $tournament_id, $date_from, $date_to);

        $modifiedDateTo = date("Y-m-d", strtotime($urlParams['date_to']) + 86500);

        // Get an instance of the Entity Manager
        $em = $this->container->get('doctrine')->getManager();

        // get the filter helper service
        $filterHelper = $this->container->get('app.filter.helper');

        // set the fields for the filter form
        $fields = array('tournament', 'user', 'date_from', 'date_to');

        // set the input data for the filter form and create it
        $formSourceData = $filterHelper->getFormSourceData($user, $urlParams, $fields);
        $formInputData = $filterHelper->getFormInputData($request, $urlParams, $fields, $formSourceData);
        $filterForm = $filterHelper->createForm($fields, $formInputData);
        $filterForm->handleRequest($request);

        // if the filter form is submitted, redirect with appropriate url path parameters
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $submittedParams = $filterHelper->actionOnFormSubmit($filterForm, $fields);

            return $this->redirectToRoute('history_index', $submittedParams);
        }

        // get finished scored matches and the user's predictions for them
        $matches = $em->getRepository(MatchEntity::class)
            ->getAlreadyScored($formSourceData['user_selected'], $urlParams['tournament_id'], $urlParams['date_from'], $modifiedDateTo);
        $predictions = $em->getRepository(Prediction::class)
            ->getAlreadyScored($formSourceData['user_selected'], $urlParams['tournament_id'], $urlParams['date_from'], $modifiedDateTo);

        // get user standings and set them as global Twig var
        $this->container->get('app.twig.helper')->setUserScores($user);

        // rendering the view and returning the response
        return $this->render(
            'History/index.html.twig',
            array(
                'matches' => $matches,
                'predictions' => $predictions,
                'filter_form' => $filterForm->createView()
            )
        );
    }
}
