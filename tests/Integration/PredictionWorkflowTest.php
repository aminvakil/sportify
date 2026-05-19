<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Entity\Match;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\Score;
use Devlabs\SportifyBundle\Entity\Tournament;
use Devlabs\SportifyBundle\Entity\User;

class PredictionWorkflowTest extends DatabaseTestCase
{
    public function testTournamentUsersPredictionsAndResultsWorkflow()
    {
        $tournament = $this->createTournament('Integration Cup');
        $homeTeam = $this->createTeam('Home FC', $tournament);
        $awayTeam = $this->createTeam('Away FC', $tournament);
        $finishedMatch = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('-1 day'), 2, 1);
        $upcomingMatch = $this->createMatch($tournament, $homeTeam, $awayTeam, new \DateTime('+1 day'));

        $exactUser = $this->createUser('exact_user');
        $outcomeUser = $this->createUser('outcome_user');

        $tournamentsHelper = self::$kernel->getContainer()->get('app.tournaments.helper');
        $tournamentsHelper->joinTournament($exactUser, $tournament);
        $tournamentsHelper->joinTournament($outcomeUser, $tournament);
        self::$kernel->getContainer()->get('app.score_updater')->updateUserPositionsForTournament($tournament->getId());

        $this->createPrediction($exactUser, $finishedMatch, 2, 1);
        $this->createPrediction($outcomeUser, $finishedMatch, 1, 0);

        $predictionRepository = $this->em->getRepository(Prediction::class);
        $notScored = $predictionRepository->getFinishedNotScored($exactUser);
        $this->assertArrayHasKey($finishedMatch->getId(), $notScored);

        $modifiedTournaments = self::$kernel->getContainer()->get('app.score_updater')->updateAll();
        $this->assertCount(1, $modifiedTournaments);

        $this->em->clear();

        $finishedMatch = $this->em->getRepository(Match::class)->find($finishedMatch->getId());
        $upcomingMatch = $this->em->getRepository(Match::class)->find($upcomingMatch->getId());
        $exactUser = $this->em->getRepository(User::class)->find($exactUser->getId());
        $outcomeUser = $this->em->getRepository(User::class)->find($outcomeUser->getId());
        $tournament = $this->em->getRepository(Tournament::class)->find($tournament->getId());

        $exactPrediction = $predictionRepository->getOneByUserAndMatch($exactUser, $finishedMatch);
        $outcomePrediction = $predictionRepository->getOneByUserAndMatch($outcomeUser, $finishedMatch);

        $this->assertSame(Prediction::POINTS_EXACT, $exactPrediction->getPoints());
        $this->assertSame(Prediction::POINTS_OUTCOME, $outcomePrediction->getPoints());
        $this->assertSame('Home FC', $exactPrediction->getHomeTeamName());
        $this->assertSame('Away FC', $exactPrediction->getAwayTeamName());
        $this->assertSame(2, $exactPrediction->getResultHomeGoals());
        $this->assertSame(1, $exactPrediction->getResultAwayGoals());

        $scoreRepository = $this->em->getRepository(Score::class);
        $exactScore = $scoreRepository->getByUserAndTournament($exactUser, $tournament);
        $outcomeScore = $scoreRepository->getByUserAndTournament($outcomeUser, $tournament);

        $this->assertSame(3, $exactScore->getPoints());
        $this->assertSame(1, $outcomeScore->getPoints());
        $this->assertSame(100, $exactScore->getExactPredictionPercentage());
        $this->assertSame(0, $outcomeScore->getExactPredictionPercentage());
        $this->assertSame(1, $exactScore->getPosNew());
        $this->assertSame(2, $outcomeScore->getPosNew());

        $alreadyScored = $predictionRepository->getAlreadyScored($exactUser, 'all', '2000-01-01', '2100-01-01');
        $this->assertArrayHasKey($finishedMatch->getId(), $alreadyScored);

        $matchesHelper = self::$kernel->getContainer()->get('app.matches.helper');
        $newPrediction = $matchesHelper->getPrediction($exactUser, $upcomingMatch, array());
        $this->assertSame('BET', $matchesHelper->getPredictionButton($newPrediction));
        $this->assertSame($upcomingMatch->getId(), $newPrediction->getMatchId()->getId());
        $this->assertSame($exactUser->getId(), $newPrediction->getUserId()->getId());

        $historyParams = self::$kernel->getContainer()->get('app.history.helper')
            ->setCurrentUser($exactUser)
            ->initUrlParams('empty', 'empty', 'empty', 'empty');
        $this->assertSame($exactUser->getId(), $historyParams['user_id']);
        $this->assertSame('all', $historyParams['tournament_id']);
    }
}
