<?php

namespace Devlabs\SportifyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ScoresUpdateController
 * @package Devlabs\SportifyBundle\Controller
 */
class ScoresUpdateController extends AbstractController
{
    public function updateAllAction()
    {
        // if user is not logged in, redirect to login page
        if (!is_object($user = $this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        // Get the ScoreUpdater service and update all scores
        $tournamentsModified = $this->container->get('app.score_updater')->updateAll();

        if (count($tournamentsModified) > 0) {
            $slackText = 'Match results and standings updated for tournament(s):';

            foreach ($tournamentsModified as $tournament) {
                $slackText = $slackText . "\n" . $tournament->getName();
            }

            // Get instance of the Slack service and send notification
            $this->container->get('app.slack')->setText($slackText)->post();
        }

        // redirect to the Home page
        return $this->redirectToRoute('home');
    }

    public function updateUserPositionsForTournamentAction($tournament_id)
    {
        // if user is not logged in, redirect to login page
        if (!is_object($user = $this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        // Get the ScoreUpdater service and update user positions in tournament
        $this->container->get('app.score_updater')->updateUserPositionsForTournament($tournament_id);

        // redirect to the Home page
        return $this->redirectToRoute('home');
    }
}
