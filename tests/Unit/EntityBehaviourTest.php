<?php

namespace Tests\Unit;

if (!defined('WEB_DIRECTORY')) {
    define('WEB_DIRECTORY', __DIR__.'/../../public');
}

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\PredictionChampion;
use Devlabs\SportifyBundle\Entity\Score;
use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;
use Devlabs\SportifyBundle\Entity\User;

class EntityBehaviourTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider scoreLineProvider
     */
    public function testMatchAndPredictionOutcomes($homeGoals, $awayGoals, $expectedOutcome)
    {
        $match = new MatchEntity();
        $match->setHomeGoals($homeGoals);
        $match->setAwayGoals($awayGoals);

        $prediction = new Prediction();
        $prediction->setHomeGoals($homeGoals);
        $prediction->setAwayGoals($awayGoals);

        $this->assertSame($expectedOutcome, $match->getResultOutcome());
        $this->assertSame($expectedOutcome, $prediction->getResultOutcome());
    }

    public function scoreLineProvider()
    {
        return array(
            array(2, 1, '1'),
            array(0, 3, '2'),
            array(1, 1, 'X'),
        );
    }

    public function testPredictionCalculatesExactOutcomeAndMissedPoints()
    {
        $match = new MatchEntity();
        $match->setHomeGoals(2);
        $match->setAwayGoals(1);

        $exact = new Prediction();
        $exact->setHomeGoals(2);
        $exact->setAwayGoals(1);

        $outcome = new Prediction();
        $outcome->setHomeGoals(3);
        $outcome->setAwayGoals(0);

        $missed = new Prediction();
        $missed->setHomeGoals(0);
        $missed->setAwayGoals(2);

        $this->assertSame(Prediction::POINTS_EXACT, $exact->calculatePoints($match));
        $this->assertSame(Prediction::POINTS_OUTCOME, $outcome->calculatePoints($match));
        $this->assertSame(0, $missed->calculatePoints($match));
    }

    public function testMatchStateAndDisplayHelpers()
    {
        $tournament = new Tournament();
        $tournament->setId(9);
        $tournament->setName('World Cup');

        $homeTeam = new Team();
        $homeTeam->setName('Home');

        $awayTeam = new Team();
        $awayTeam->setName('Away');

        $pastMatch = new MatchEntity();
        $pastMatch->setId(123);
        $pastMatch->setTournamentId($tournament);
        $pastMatch->setHomeTeamId($homeTeam);
        $pastMatch->setAwayTeamId($awayTeam);
        $pastMatch->setDatetime(new \DateTime('-1 minute'));

        $futureMatch = new MatchEntity();
        $futureMatch->setDatetime(new \DateTime('+1 day'));

        $this->assertTrue($pastMatch->hasStarted());
        $this->assertFalse($futureMatch->hasStarted());
        $this->assertFalse($pastMatch->getDisabledAttribute());
        $pastMatch->setDisabledAttribute();
        $this->assertTrue($pastMatch->getDisabledAttribute());
        $this->assertFalse($pastMatch->getNotificationSent());
        $pastMatch->setNotificationSent(1);
        $this->assertTrue($pastMatch->getNotificationSent());
        $this->assertSame('Home', $pastMatch->getHomeTeamName());
        $this->assertSame('Away', $pastMatch->getAwayTeamName());
        $this->assertSame('World Cup', $pastMatch->getTournamentName());
        $this->assertSame('123', (string) $pastMatch);
    }

    public function testTeamLogoReplacementDeletesExistingWebLogoPath()
    {
        $team = new Team();
        $team->setId(98765);
        $logoPath = WEB_DIRECTORY.'/img/team_logos/team_logo_98765.svg';
        $sourcePath = tempnam(sys_get_temp_dir(), 'sportify-team-logo-');

        file_put_contents($logoPath, '<svg>old</svg>');
        file_put_contents($sourcePath, '<svg>new</svg>');

        try {
            $team->setTeamLogo($sourcePath, 'svg');

            $this->assertSame('<svg>new</svg>', file_get_contents($logoPath));
        } finally {
            if (is_file($logoPath)) {
                unlink($logoPath);
            }
            if (is_file($sourcePath)) {
                unlink($sourcePath);
            }
        }
    }

    public function testInvalidTeamLogoFileFallsBackToDefault()
    {
        $team = new Team();
        $team->setId(98766);
        $logoPath = WEB_DIRECTORY.'/img/team_logos/team_logo_98766.svg';

        file_put_contents($logoPath, '<html>not an image</html>');

        try {
            $this->assertFalse($team->hasTeamLogo());
            $this->assertSame('img/default_team_logo.png', $team->getTeamLogo());
        } finally {
            if (is_file($logoPath)) {
                unlink($logoPath);
            }
        }
    }

    public function testInvalidTournamentLogoFileFallsBackToDefault()
    {
        $tournament = new Tournament();
        $tournament->setId(98767);
        $logoPath = WEB_DIRECTORY.'/img/tournament_logos/tournament_logo_98767.svg';

        file_put_contents($logoPath, '<html>not an image</html>');

        try {
            $this->assertFalse($tournament->hasLogo());
            $this->assertSame('img/default_tournament_logo.png', $tournament->getLogo());
        } finally {
            if (is_file($logoPath)) {
                unlink($logoPath);
            }
        }
    }

    public function testInvalidTeamLogoImportThrowsAndKeepsExistingLogo()
    {
        $team = new Team();
        $team->setId(98768);
        $logoPath = WEB_DIRECTORY.'/img/team_logos/team_logo_98768.svg';
        $sourcePath = tempnam(sys_get_temp_dir(), 'sportify-bad-team-logo-');

        file_put_contents($logoPath, '<svg>old</svg>');
        file_put_contents($sourcePath, 'not an image');

        try {
            $exception = null;
            try {
                $team->setTeamLogo($sourcePath, 'png');
            } catch (\RuntimeException $e) {
                $exception = $e;
            }

            $this->assertInstanceOf(\RuntimeException::class, $exception);
            $this->assertStringContainsString('Could not decode team logo image', $exception->getMessage());
            $this->assertSame('<svg>old</svg>', file_get_contents($logoPath));
        } finally {
            if (is_file($logoPath)) {
                unlink($logoPath);
            }
            if (is_file($sourcePath)) {
                unlink($sourcePath);
            }
        }
    }

    public function testInvalidTournamentLogoImportThrowsAndKeepsExistingLogo()
    {
        $tournament = new Tournament();
        $tournament->setId(98769);
        $logoPath = WEB_DIRECTORY.'/img/tournament_logos/tournament_logo_98769.svg';
        $sourcePath = tempnam(sys_get_temp_dir(), 'sportify-bad-tournament-logo-');

        file_put_contents($logoPath, '<svg>old</svg>');
        file_put_contents($sourcePath, 'not an image');

        try {
            $exception = null;
            try {
                $tournament->setLogo($sourcePath, 'png');
            } catch (\RuntimeException $e) {
                $exception = $e;
            }

            $this->assertInstanceOf(\RuntimeException::class, $exception);
            $this->assertStringContainsString('Could not decode tournament logo image', $exception->getMessage());
            $this->assertSame('<svg>old</svg>', file_get_contents($logoPath));
        } finally {
            if (is_file($logoPath)) {
                unlink($logoPath);
            }
            if (is_file($sourcePath)) {
                unlink($sourcePath);
            }
        }
    }

    public function testScoreUpdatesPointsAndDisplaysRelatedData()
    {
        $user = new User();
        $user->setUsername('player');
        $user->setEmail('player@example.com');

        $tournament = new Tournament();
        $tournament->setName('League');

        $score = new Score();
        $score->setUserId($user);
        $score->setTournamentId($tournament);
        $score->setPoints(2);
        $score->updatePoints(3);
        $score->setPointsOld(1);
        $score->setPosOld(4);
        $score->setPosNew(2);
        $score->setExactPredictionPercentage(50);

        $this->assertSame(5, $score->getPoints());
        $this->assertSame(1, $score->getPointsOld());
        $this->assertSame(4, $score->getPosOld());
        $this->assertSame(2, $score->getPosNew());
        $this->assertSame(50, $score->getExactPredictionPercentage());
        $this->assertSame('player', $score->getUsername());
        $this->assertSame('player@example.com', $score->getUserEmail());
        $this->assertSame('League', $score->getTournamentName());
    }

    public function testChampionPredictionCalculatesPointsAndDisplaysRelatedData()
    {
        $user = new User();
        $user->setUsername('champion_user');
        $user->setEmail('champion@example.com');

        $champion = new Team();
        $champion->setName('Winner');

        $otherTeam = new Team();
        $otherTeam->setName('Runner up');

        $tournament = new Tournament();
        $tournament->setName('Cup');
        $tournament->setChampionTeamId($champion);

        $winningPrediction = new PredictionChampion();
        $winningPrediction->setUserId($user);
        $winningPrediction->setTournamentId($tournament);
        $winningPrediction->setTeamId($champion);
        $winningPrediction->setPoints(PredictionChampion::POINTS_WIN);
        $winningPrediction->setScoreAdded(1);

        $losingPrediction = new PredictionChampion();
        $losingPrediction->setTournamentId($tournament);
        $losingPrediction->setTeamId($otherTeam);

        $this->assertSame(PredictionChampion::POINTS_WIN, $winningPrediction->calculatePoints());
        $this->assertSame(0, $losingPrediction->calculatePoints());
        $this->assertSame(PredictionChampion::POINTS_WIN, $winningPrediction->getPoints());
        $this->assertTrue((bool) $winningPrediction->getScoreAdded());
        $this->assertSame('champion_user', $winningPrediction->getUsername());
        $this->assertSame('champion@example.com', $winningPrediction->getUserEmail());
        $this->assertSame('Cup', $winningPrediction->getTournamentName());
        $this->assertSame('Winner', $winningPrediction->getTeamName());
    }

    public function testEntityCollectionDefaultsAndStringValues()
    {
        $team = new Team();
        $team->setName('Team Name');

        $tournament = new Tournament();
        $tournament->setId(55);

        $user = new User();

        $match = new MatchEntity();
        $prediction = new Prediction();
        $score = new Score();
        $championPrediction = new PredictionChampion();

        $team->addTournament($tournament);
        $tournament->addTeam($team);
        $user->addPrediction($prediction);
        $user->addScore($score);
        $user->addPredictionsChampion($championPrediction);
        $match->addPrediction($prediction);

        $this->assertCount(1, $team->getTournaments());
        $this->assertCount(1, $tournament->getTeams());
        $this->assertCount(1, $user->getPredictions());
        $this->assertCount(1, $user->getScores());
        $this->assertCount(1, $user->getPredictionsChampion());
        $this->assertCount(1, $match->getPredictions());
        $this->assertSame('Team Name', (string) $team);
        $this->assertSame('55', (string) $tournament);
        $this->assertSame('slack_username', $user->getSlackUsername());
        $user->setSlackUsername('custom-slack');
        $this->assertSame('custom-slack', $user->getSlackUsername());
    }
}
