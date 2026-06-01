<?php

namespace Devlabs\SportifyBundle\Command;

use Devlabs\SportifyBundle\Services\Odds\TheOddsApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckWorldCupOddsCommand extends Command
{
    private const SPORT_KEY = 'soccer_fifa_world_cup';

    private $oddsApi;

    public function __construct(TheOddsApi $oddsApi)
    {
        parent::__construct();
        $this->oddsApi = $oddsApi;
    }

    protected function configure(): void
    {
        $this
            ->setName('sportify:odds:check-world-cup-2026')
            ->setDescription('Locally check The Odds API snapshots for FIFA World Cup 2026 first-two-day fixtures.')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'UTC start date for the check.', '2026-06-11')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Number of UTC calendar days to check.', '2')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->oddsApi->hasConfiguredApiToken()) {
            $output->writeln('<error>Set odds_api.token in app/config/parameters.yml before running this local check.</error>');

            return 1;
        }

        $days = (int) $input->getOption('days');
        if ($days < 1) {
            $output->writeln('<error>The --days option must be at least 1.</error>');

            return 1;
        }

        try {
            $from = new \DateTimeImmutable($input->getOption('from').' 00:00:00', new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            $output->writeln('<error>The --from option must be a valid date, for example 2026-06-11.</error>');

            return 1;
        }
        $to = $from->modify('+'.$days.' days');

        $output->writeln('Checking The Odds API sport key '.self::SPORT_KEY);
        $output->writeln('Window: '.$from->format('Y-m-d\TH:i:s\Z').' to '.$to->format('Y-m-d\TH:i:s\Z').' (exclusive)');

        $events = $this->oddsApi->fetchEventProbabilitySnapshots(self::SPORT_KEY, $from, $to);
        if ($events === null) {
            $output->writeln('<error>Unable to retrieve events/odds from The Odds API.</error>');

            return 1;
        }

        if (!$events) {
            $output->writeln('No FIFA World Cup events returned for this window.');

            return 0;
        }

        $complete = 0;
        foreach ($events as $event) {
            $line = $event['commence_time'].' '.$event['home_team'].' vs '.$event['away_team'];
            if ($event['snapshot'] === null) {
                $output->writeln($line.' — no complete h2h odds snapshot');
                continue;
            }

            $complete++;
            $snapshot = $event['snapshot'];
            $output->writeln($line.' — home '.$snapshot['home_win_probability_percent'].'%, draw '.$snapshot['draw_probability_percent'].'%, away '.$snapshot['away_win_probability_percent'].'% ('.$snapshot['source'].')');
        }

        $output->writeln('Summary: '.count($events).' event(s), '.$complete.' complete odds snapshot(s).');

        return 0;
    }
}
