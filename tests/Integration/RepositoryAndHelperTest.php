<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

use Devlabs\SportifyBundle\Entity\ApiMapping;
use Devlabs\SportifyBundle\Entity\Match;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\PredictionChampion;
use Devlabs\SportifyBundle\Entity\Score;
use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;
use Devlabs\SportifyBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class RepositoryAndHelperTest extends DatabaseTestCase
{
    public function testTournamentTeamScoreAndFilterHelpers()
    {
        $cup = $this->createTournament('Cup');
        $league = $this->createTournament('League');
        $cupTeam = $this->createTeam('Cup Team', $cup);
        $this->createTeam('League Team', $league);

        $alice = $this->createUser('alice');
        $bob = $this->createUser('bob');
        $disabled = $this->createUser('disabled', false);

        $tournamentsHelper = self::$kernel->getContainer()->get('app.tournaments.helper');
        $this->assertSame('JOIN', $tournamentsHelper->getButtonAction($cup, array()));
        $this->assertSame('LEAVE', $tournamentsHelper->getButtonAction($cup, array($cup)));
        $this->assertSame(array('tournament_id' => $cup->getId(), 'button_action' => 'JOIN'), $tournamentsHelper->getFormInputData($cup, array()));

        $tournamentsHelper->joinTournament($alice, $cup);
        $tournamentsHelper->joinTournament($bob, $cup);
        $tournamentsHelper->joinTournament($alice, $league);
        self::$kernel->getContainer()->get('app.score_updater')->updateUserPositionsForTournament($cup->getId());

        $tournamentRepository = $this->em->getRepository(Tournament::class);
        $this->assertSame($cup->getId(), $tournamentRepository->getFirst()->getId());
        $this->assertSame(array($cup->getId(), $league->getId()), $this->ids($tournamentRepository->getJoined($alice)));

        $teamRepository = $this->em->getRepository(Team::class);
        $this->assertSame($cupTeam->getId(), $teamRepository->getFirstByTournament($cup)->getId());
        $this->assertSame(array($cupTeam->getId()), $this->ids($teamRepository->getAllByTournament($cup)));

        $scoreRepository = $this->em->getRepository(Score::class);
        $aliceCupScore = $scoreRepository->getByUserAndTournament($alice, $cup);
        $bobCupScore = $scoreRepository->getByUserAndTournament($bob, $cup);
        $aliceCupScore->setPoints(3);
        $aliceCupScore->setExactPredictionPercentage(50);
        $bobCupScore->setPoints(3);
        $bobCupScore->setExactPredictionPercentage(100);
        $this->em->flush();

        $this->assertSame($aliceCupScore->getId(), $scoreRepository->getByUser($alice)[$cup->getId()]->getId());
        $this->assertSame($aliceCupScore->getId(), $scoreRepository->getAllHashed()[$cup->getId()][$alice->getId()]->getId());
        $this->assertSame(array($bobCupScore->getId(), $aliceCupScore->getId()), $this->ids($scoreRepository->getByTournamentOrderByPoints($cup)));

        $userRepository = $this->em->getRepository(User::class);
        $this->assertSame(array($alice->getId(), $bob->getId()), $this->ids($userRepository->getAllEnabled()));

        $urlParams = array(
            'user_id' => $alice->getId(),
            'tournament_id' => $cup->getId(),
            'date_from' => '2020-01-01',
            'date_to' => '2020-01-31',
        );
        $filterHelper = self::$kernel->getContainer()->get('app.filter.helper');
        $sourceData = $filterHelper->getFormSourceData($alice, $urlParams, array('tournament', 'user'));
        $inputData = $filterHelper->getFormInputData(new Request(), $urlParams, array('tournament', 'user', 'date_from', 'date_to'), $sourceData);

        $this->assertSame($cup->getId(), $inputData['tournament']['data']->getId());
        $this->assertSame(array($cup->getId(), $league->getId()), $this->ids($inputData['tournament']['choices']));
        $this->assertSame($alice->getId(), $inputData['user']['data']->getId());
        $this->assertSame(array($alice->getId(), $bob->getId()), $this->ids($inputData['user']['choices']));
        $this->assertSame('2020-01-01', $inputData['date_from']);
        $this->assertSame('2020-01-31', $inputData['date_to']);

        $tournamentsHelper->leaveTournament($alice, $league);
        $this->assertSame(array($cup->getId()), $this->ids($tournamentRepository->getJoined($alice)));
    }

    public function testMatchPredictionAndUserRepositoryFilters()
    {
        $cup = $this->createTournament('Repository Cup');
        $otherCup = $this->createTournament('Other Cup');
        $homeTeam = $this->createTeam('Alpha', $cup);
        $awayTeam = $this->createTeam('Beta', $cup);
        $otherHomeTeam = $this->createTeam('Gamma', $otherCup);
        $otherAwayTeam = $this->createTeam('Delta', $otherCup);

        $user = $this->createUser('filter_user');
        $otherUser = $this->createUser('other_user');
        $this->createUser('disabled_user', false);

        $tournamentsHelper = self::$kernel->getContainer()->get('app.tournaments.helper');
        $tournamentsHelper->joinTournament($user, $cup);
        $tournamentsHelper->joinTournament($otherUser, $cup);
        $tournamentsHelper->joinTournament($user, $otherCup);

        $upcomingUnpredicted = $this->createMatch($cup, $homeTeam, $awayTeam, new \DateTime('2020-01-10 12:00:00'));
        $upcomingPredicted = $this->createMatch($cup, $awayTeam, $homeTeam, new \DateTime('2020-01-11 12:00:00'));
        $finishedUnscored = $this->createMatch($cup, $homeTeam, $awayTeam, new \DateTime('2020-01-12 12:00:00'), 2, 1);
        $finishedScored = $this->createMatch($cup, $awayTeam, $homeTeam, new \DateTime('2020-01-13 12:00:00'), 0, 0);
        $finishedNoPrediction = $this->createMatch($cup, $homeTeam, $awayTeam, new \DateTime('2020-01-14 12:00:00'), 1, 3);
        $otherTournamentMatch = $this->createMatch($otherCup, $otherHomeTeam, $otherAwayTeam, new \DateTime('2020-01-15 12:00:00'));

        $this->createPrediction($user, $upcomingPredicted, 1, 1);
        $this->createPrediction($user, $finishedUnscored, 2, 1);
        $this->createPrediction($user, $finishedScored, 0, 0, 1, Prediction::POINTS_EXACT);
        $this->createPrediction($otherUser, $upcomingUnpredicted, 0, 0);

        $matchRepository = $this->em->getRepository(Match::class);
        $predictionRepository = $this->em->getRepository(Prediction::class);
        $userRepository = $this->em->getRepository(User::class);

        $this->assertSame(array($upcomingPredicted->getId()), array_keys($predictionRepository->getNotScored($user, 'all', '2020-01-01', '2020-01-31')));
        $this->assertSame(array($finishedScored->getId()), array_keys($predictionRepository->getAlreadyScored($user, 'all', '2020-01-01', '2020-01-31')));
        $this->assertSame(array($finishedUnscored->getId()), array_keys($predictionRepository->getFinishedNotScored($user)));
        $this->assertSame(array($finishedScored->getId()), $this->matchIds($predictionRepository->getExactPredictionsByUserAndTournament($user, $cup)));
        $this->assertSame(array($upcomingPredicted->getId(), $finishedUnscored->getId(), $finishedScored->getId()), $this->matchIds($predictionRepository->findFiltered($user, array('tournament' => $cup, 'date_from' => '2020-01-01', 'date_to' => '2020-01-31'))));

        $this->assertSame(array($upcomingUnpredicted->getId(), $upcomingPredicted->getId(), $otherTournamentMatch->getId()), $this->ids($matchRepository->getNotScored($user, 'all', '2020-01-01', '2020-01-31')));
        $this->assertSame(array($finishedNoPrediction->getId(), $finishedScored->getId()), $this->ids($matchRepository->getAlreadyScored($user, 'all', '2020-01-01', '2020-01-31')));
        $this->assertSame(array($finishedUnscored->getId()), array_keys($matchRepository->getFinishedNotScored()));
        $this->assertSame(array($finishedUnscored->getId(), $finishedScored->getId(), $finishedNoPrediction->getId()), $this->ids($matchRepository->getFinishedByTournament($cup)));
        $this->assertSame(array($upcomingUnpredicted->getId(), $upcomingPredicted->getId(), $finishedUnscored->getId(), $finishedScored->getId(), $finishedNoPrediction->getId()), $this->ids($matchRepository->getAllByTournament($cup)));
        $this->assertSame(array($upcomingPredicted->getId(), $finishedUnscored->getId()), $this->ids($matchRepository->getAllByTournamentAndTimeRange($cup, '2020-01-11', '2020-01-12 23:59:59')));
        $this->assertSame(array($upcomingUnpredicted->getId(), $upcomingPredicted->getId(), $finishedUnscored->getId(), $finishedScored->getId(), $finishedNoPrediction->getId()), $this->ids($matchRepository->findFiltered(array('tournament' => $cup, 'team' => 'Alpha'))));
        $this->assertSame(array($upcomingUnpredicted->getId(), $upcomingPredicted->getId(), $finishedUnscored->getId(), $finishedScored->getId(), $finishedNoPrediction->getId(), $otherTournamentMatch->getId()), $this->ids($matchRepository->getUpcoming('2020-01-01', '2020-01-31')));

        $this->assertSame(array($user->getId(), $otherUser->getId()), $this->ids($userRepository->getNotPredictedByMatch($finishedNoPrediction)));
        $this->assertSame(array($otherUser->getId()), $this->ids($userRepository->getNotPredictedByMatch($upcomingPredicted)));
    }

    public function testChampionPredictionScoringAndRepositoryMethods()
    {
        $tournament = $this->createTournament('Champion Cup');
        $winner = $this->createTeam('Winner', $tournament);
        $loser = $this->createTeam('Loser', $tournament);
        $rightUser = $this->createUser('right_user');
        $wrongUser = $this->createUser('wrong_user');

        $tournamentsHelper = self::$kernel->getContainer()->get('app.tournaments.helper');
        $tournamentsHelper->joinTournament($rightUser, $tournament);
        $tournamentsHelper->joinTournament($wrongUser, $tournament);

        $championHelper = self::$kernel->getContainer()->get('app.champion.helper');
        $newPrediction = $championHelper->getPredictionChampion($rightUser, $tournament);
        $formInputData = $championHelper->getFormInputData($newPrediction, $tournament);
        $this->assertSame('BET', $formInputData['button_action']);
        $teamChoiceIds = $this->ids($formInputData['team']['choices']);
        sort($teamChoiceIds);
        $this->assertContains($formInputData['team']['data']->getId(), array($winner->getId(), $loser->getId()));
        $this->assertSame(array($winner->getId(), $loser->getId()), $teamChoiceIds);

        $rightPrediction = $this->createChampionPrediction($rightUser, $tournament, $winner);
        $wrongPrediction = $this->createChampionPrediction($wrongUser, $tournament, $loser);
        $tournament->setChampionTeamId($winner);
        $this->em->flush();

        $modifiedTournaments = self::$kernel->getContainer()->get('app.score_updater')->updateAll();
        $this->assertSame(array($tournament->getId()), $this->ids($modifiedTournaments));

        $this->em->clear();

        $tournament = $this->em->getRepository(Tournament::class)->find($tournament->getId());
        $rightUser = $this->em->getRepository(User::class)->find($rightUser->getId());
        $wrongUser = $this->em->getRepository(User::class)->find($wrongUser->getId());
        $rightPrediction = $this->em->getRepository(PredictionChampion::class)->find($rightPrediction->getId());
        $wrongPrediction = $this->em->getRepository(PredictionChampion::class)->find($wrongPrediction->getId());

        $predictionChampionRepository = $this->em->getRepository(PredictionChampion::class);
        $this->assertSame($rightPrediction->getId(), $predictionChampionRepository->getByUserAndTournament($rightUser, $tournament)->getId());
        $this->assertSame(array(), $predictionChampionRepository->getNotScoredByTournament($tournament));
        $this->assertSame(5, $rightPrediction->getPoints());
        $this->assertSame(0, $wrongPrediction->getPoints());
        $this->assertTrue((bool) $rightPrediction->getScoreAdded());
        $this->assertTrue((bool) $wrongPrediction->getScoreAdded());

        $scoreRepository = $this->em->getRepository(Score::class);
        $this->assertSame(5, $scoreRepository->getByUserAndTournament($rightUser, $tournament)->getPoints());
        $this->assertSame(0, $scoreRepository->getByUserAndTournament($wrongUser, $tournament)->getPoints());
    }

    public function testApiMappingRepositoryLookups()
    {
        $tournament = $this->createTournament('Mapped Cup');
        $mapping = $this->createApiMapping($tournament, 'Tournament', 'football-data', 987);
        $repository = $this->em->getRepository(ApiMapping::class);

        $this->assertSame($mapping->getId(), $repository->getByEntityTypeAndApiObjectId('Tournament', 'football-data', 987)->getId());
        $this->assertSame($mapping->getId(), $repository->getByEntityAndApiProvider($tournament, 'Tournament', 'football-data')->getId());
        $this->assertNull($repository->getByEntityTypeAndApiObjectId('Tournament', 'football-data', 111));
    }

    private function ids(array $entities)
    {
        $ids = array();

        foreach ($entities as $entity) {
            $ids[] = $entity->getId();
        }

        return $ids;
    }

    private function matchIds(array $predictions)
    {
        $ids = array();

        foreach ($predictions as $prediction) {
            $ids[] = $prediction->getMatchId()->getId();
        }

        return $ids;
    }
}
