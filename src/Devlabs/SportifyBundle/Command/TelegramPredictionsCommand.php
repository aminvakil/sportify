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
        $matchTexts = array();

        foreach ($matches as $match) {
            $matchTexts[$match->getId()] = $match->getHomeTeamName().' - '.$match->getAwayTeamName();
        }

        $lines = array(
            'mid  match                         user                 pred',
            '---  ----------------------------  -------------------  -----',
        );

        foreach ($predictions as $prediction) {
            $match = $prediction->getMatchId();
            $lines[] = sprintf(
                '%-4s %-28s  %-19s  %s-%s',
                $match->getId(),
                $this->shorten($matchTexts[$match->getId()], 28),
                $this->shorten($prediction->getUserId()->getUsername(), 19),
                $prediction->getHomeGoals(),
                $prediction->getAwayGoals()
            );
        }

        if (count($lines) === 2) {
            $lines[] = 'No predictions.';
        }

        return 'Predictions for matches started between '
            .$dateFrom->format('Y-m-d H:i')
            .' and '
            .$dateTo->format('Y-m-d H:i')
            .":\n```\n"
            .implode("\n", $lines)
            ."\n```";
    }

    private function shorten($value, $length)
    {
        if (strlen($value) <= $length) {
            return $value;
        }

        return substr($value, 0, $length - 1).'…';
    }
}
