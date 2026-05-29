<?php

namespace Devlabs\SportifyBundle\Command;

use Devlabs\SportifyBundle\Entity\MatchEntity;
use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Services\Telegram;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TelegramPredictionsCommand
 * @package Devlabs\SportifyBundle\Command
 */
class TelegramPredictionsCommand extends Command
{
    private $em;
    private $telegram;

    public function __construct(EntityManagerInterface $em, Telegram $telegram)
    {
        parent::__construct();
        $this->em = $em;
        $this->telegram = $telegram;
    }

    protected function configure(): void
    {
        $this
            ->setName('sportify:telegram:send-predictions')
            ->setDescription('Send recently started match predictions to Telegram')
            ->addOption(
                'lookback-minutes',
                null,
                InputOption::VALUE_REQUIRED,
                'How many minutes back to check for started matches',
                5
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lookbackMinutes = (int) $input->getOption('lookback-minutes');

        if ($lookbackMinutes < 1) {
            $output->writeln('lookback-minutes must be at least 1.');

            return Command::FAILURE;
        }

        $dateTo = new \DateTime();
        $dateFrom = (clone $dateTo)->modify('-'.$lookbackMinutes.' minutes');

        $matches = $this->em->getRepository(MatchEntity::class)
            ->getStartedAwaitingPredictionNotification($dateFrom, $dateTo);

        if (!$matches) {
            $output->writeln('No recently started matches found.');

            return Command::SUCCESS;
        }

        $predictions = $this->em->getRepository(Prediction::class)
            ->getByMatches($matches);

        $message = $this->formatMessage($dateFrom, $dateTo, $matches, $predictions);
        $response = $this->telegram->sendMessage($message);

        if (!$response || $response->getStatusCode() !== 200) {
            $statusCode = $response ? $response->getStatusCode() : 'none';
            $output->writeln('Telegram message failed. Status: '.$statusCode);

            return Command::FAILURE;
        }

        foreach ($matches as $match) {
            $match->setPredictionsNotificationSent('1');
            $this->em->persist($match);
        }

        $this->em->flush();
        $output->writeln('Telegram predictions sent for '.count($matches).' match(es).');

        return Command::SUCCESS;
    }

    private function formatMessage(\DateTime $dateFrom, \DateTime $dateTo, array $matches, array $predictions)
    {
        $predictionsByMatch = array();
        foreach ($predictions as $prediction) {
            $predictionsByMatch[$prediction->getMatchId()->getId()][] = $prediction;
        }

        $lines = array();
        foreach ($matches as $match) {
            $lines[] = $match->getHomeTeamName().' - '.$match->getAwayTeamName();
            $lines[] = 'Probabilities: '.$this->formatProbabilitySnapshot($match);
            $lines[] = 'user                 pred   outcome';
            $lines[] = '-------------------  -----  --------';

            if (!isset($predictionsByMatch[$match->getId()])) {
                $lines[] = 'No predictions.';
                $lines[] = '';
                continue;
            }

            foreach ($predictionsByMatch[$match->getId()] as $prediction) {
                $lines[] = sprintf(
                    '%-19s  %s-%s    %s',
                    $this->shorten($prediction->getUserId()->getUsername(), 19),
                    $prediction->getHomeGoals(),
                    $prediction->getAwayGoals(),
                    $this->formatOutcome($prediction->getResultOutcome())
                );
            }
            $lines[] = '';
        }

        return 'Predictions for matches started between '
            .$dateFrom->format('Y-m-d H:i')
            .' and '
            .$dateTo->format('Y-m-d H:i')
            .":\n```\n"
            .rtrim(implode("\n", $lines))
            ."\n```";
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

    private function formatProbability($percent)
    {
        return $percent.'%';
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

    private function shorten($value, $length)
    {
        if (strlen($value) <= $length) {
            return $value;
        }

        return substr($value, 0, $length - 1).'…';
    }
}
