<?php

namespace Devlabs\SportifyBundle\Command;

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\Score;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DataUpdateCommand
 * @package Devlabs\SportifyBundle\Command
 */
class DataUpdateCommand extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure(): void
    {
        $this
            ->setName('sportify:data:update')
            ->setDescription('Data updates via API fetch')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'What data do you want to update?'
            )
            ->addArgument(
                'days',
                InputArgument::REQUIRED,
                'What period do you want to fetch data for? (days)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updateType = $input->getArgument('type');
        $days = $input->getArgument('days');

        $dataUpdatesManager = $this->container->get('app.data_updates.manager');
        $dataUpdated = false;
        $msgText = '';
        $logText = 'Command for updating '.$updateType.' executed at: '.date("Y-m-d H:i:s");

        if ($updateType === 'matches-fixtures') {
            // set dateFrom and dateTo to respectively today and 'number of days' on
            $dateFrom = date("Y-m-d");
            $dateTo = date("Y-m-d", time() + (3600 * 24 * $days));
            $status = $dataUpdatesManager->updateFixtures($dateFrom, $dateTo);

            if ($status['total_added'] > 0) {
                $dataUpdated = true;
                $msgText = 'Match fixtures added for next '.$days.' days. '
                    .$status['total_added'].' fixture(s) added.';
                if (isset($status['added_fixtures'])) {
                    $msgText .= $this->formatAddedFixtures($status['added_fixtures']);
                }
            }
        } elseif ($updateType === 'matches-results') {
            // set dateFrom and dateTo to respectively 'number of days' before and today
            $dateFrom = date("Y-m-d", time() - (3600 * 24 * $days));
            $dateTo = date("Y-m-d");
            $status = $dataUpdatesManager->updateFixtures($dateFrom, $dateTo);

            if ($status['total_updated'] > 0) {
                $em = $this->container->get('doctrine.orm.entity_manager');

                // Get the ScoreUpdater service and update all scores
                $scoreUpdater = $this->container->get('app.score_updater');
                $tournamentsModified = $scoreUpdater->updateAll();
                $scoredMatches = $this->getScoredMatches($em, $scoreUpdater->getLastScoredMatchIds());
                $scoredPredictions = $em->getRepository(Prediction::class)
                    ->getByMatches($scoredMatches);

                $scores = $em->getRepository(Score::class)
                    ->getAllHashed();

                $dataUpdated = true;
                $msgText = 'Match results and standings updated for tournament(s):';

                foreach ($tournamentsModified as $tournament) {
                    $msgText = $msgText."\n".$tournament->getName();
                }

                $msgText .= $this->formatScoredMatches($scoredMatches, $scoredPredictions);
                $msgText .= "\nStandings changes:";

                foreach ($tournamentsModified as $tournament) {
                    foreach ($scores[$tournament->getId()] as $score) {
                        if ($score->getPoints() == $score->getPointsOld()) {
                            continue;
                        }

                        $msgText = $msgText."\n\t"
                            .$score->getUserId()->getUsername()
                            .": Position: "
                            .$score->getPosNew()
                            ." (previous: "
                            .$score->getPosOld()
                            ."), Points: "
                            .$score->getPoints()
                            ." (gained: "
                            .($score->getPoints() - $score->getPointsOld())
                            .")";
                    }
                }
            }
        }

        if ($dataUpdated) {
            // Get instance of the Slack service and send notification
            $this->container->get('app.slack')->setText($msgText)->post();

            $logText = $logText . "\n" . $msgText . "\n";

            $telegram = $this->container->get('app.telegram');
            $telegramResponse = $telegram->sendMessage($logText);
            if ($telegramResponse->getStatusCode() >= 200 && $telegramResponse->getStatusCode() < 300) {
                $telegramResult = json_decode((string) $telegramResponse->getBody(), true);

                if ($this->shouldPinTelegramMessages() && isset($telegramResult['result']['message_id'])) {
                    $telegram->pinMessage($telegramResult['result']['message_id']);
                }
            }
        } else {
            $logText = $logText . "\n" . 'No fixtures/results added or updated.' . "\n";
        }

        $output->writeln($logText);

        return 0;
    }

    private function getScoredMatches($em, array $matchIds)
    {
        if (!$matchIds) {
            return array();
        }

        return $em->getRepository(MatchEntity::class)->findBy(array('id' => $matchIds));
    }

    private function formatScoredMatches(array $matches, array $predictions)
    {
        if (!$matches) {
            return '';
        }

        $predictionsByMatch = array();
        foreach ($predictions as $prediction) {
            $predictionsByMatch[$prediction->getMatchId()->getId()][] = $prediction;
        }

        $text = "\nScored matches:";
        foreach ($matches as $match) {
            $text .= "\n".$match->getHomeTeamName().' '.$match->getHomeGoals().'-'.$match->getAwayGoals().' '.$match->getAwayTeamName();
            $text .= "\n\tProbabilities: ".$this->formatProbabilitySnapshot($match);

            if (!isset($predictionsByMatch[$match->getId()])) {
                $text .= "\n\tNo predictions.";
                continue;
            }

            foreach ($predictionsByMatch[$match->getId()] as $prediction) {
                $text .= "\n\t"
                    .$prediction->getUserId()->getUsername()
                    .' predicted '
                    .$prediction->getHomeGoals().'-'.$prediction->getAwayGoals()
                    .' ('.$this->formatOutcome($prediction->getResultOutcome()).'): '
                    .$this->formatScoringBreakdown($prediction);
            }
        }

        return $text;
    }

    private function formatScoringBreakdown(Prediction $prediction)
    {
        if ($prediction->getScoringResult() === Prediction::SCORING_RESULT_EXACT) {
            return 'exact score, base '.$prediction->getBasePoints()
                .' + probability bonus '.$prediction->getProbabilityBonus()
                .' = '.$prediction->getTotalPoints();
        }

        if ($prediction->getScoringResult() === Prediction::SCORING_RESULT_OUTCOME) {
            return 'correct outcome, base '.$prediction->getBasePoints()
                .' + probability bonus '.$prediction->getProbabilityBonus()
                .' = '.$prediction->getTotalPoints();
        }

        return 'wrong outcome, 0 points';
    }

    private function formatProbabilitySnapshot(MatchEntity $match)
    {
        if (!$match->hasProbabilitySnapshot()) {
            return 'not available';
        }

        return 'home '.$this->formatProbability($match->getHomeWinProbabilityPercent())
            .', draw '.$this->formatProbability($match->getDrawProbabilityPercent())
            .', away '.$this->formatProbability($match->getAwayWinProbabilityPercent());
    }

    private function formatOutcome($outcome)
    {
        if ($outcome === '1') {
            return 'home win';
        }
        if ($outcome === '2') {
            return 'away win';
        }

        return 'draw';
    }

    private function formatAddedFixtures(array $addedFixtures)
    {
        if (!$addedFixtures) {
            return '';
        }

        $text = "\nAdded matches:";
        foreach ($addedFixtures as $fixture) {
            $text .= "\n".$fixture['home_team'].' vs '.$fixture['away_team']
                .': home '.$this->formatProbability($fixture['home_win_probability_percent'])
                .', draw '.$this->formatProbability($fixture['draw_probability_percent'])
                .', away '.$this->formatProbability($fixture['away_win_probability_percent']);
        }

        return $text;
    }

    private function formatProbability($percent)
    {
        return $percent.'%';
    }

    private function shouldPinTelegramMessages()
    {
        if (!$this->container->hasParameter('telegram.pin_messages')) {
            return true;
        }

        return filter_var($this->container->getParameter('telegram.pin_messages'), FILTER_VALIDATE_BOOLEAN);
    }
}
