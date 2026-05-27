<?php

namespace Devlabs\SportifyBundle\Services\Odds;

use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TheOddsApi
{
    const BASE_URI = 'https://api.the-odds-api.com';
    const REGION = 'eu';
    const MARKET = 'h2h';
    const ODDS_FORMAT = 'decimal';
    const MATCH_TOLERANCE_SECONDS = 600;

    private $container;
    private $httpClient;
    private $normalizer;
    private $activeSportKeys;

    private $sportKeyCandidates = array(
        'world cup' => array('soccer_fifa_world_cup'),
        'world cup qualifying' => array('soccer_fifa_world_cup_qualification'),
        'world cup qualification' => array('soccer_fifa_world_cup_qualification'),
        'uefa euro' => array('soccer_uefa_european_championship'),
        'euro' => array('soccer_uefa_european_championship'),
        'european championship' => array('soccer_uefa_european_championship'),
        'nations league' => array('soccer_uefa_nations_league'),
        'gold cup' => array('soccer_concacaf_gold_cup'),
        'copa america' => array('soccer_conmebol_copa_america'),
        'africa cup of nations' => array('soccer_africa_cup_of_nations'),
        'afcon' => array('soccer_africa_cup_of_nations'),
    );

    private $preferredBookmakers = array('pinnacle', 'betfair', 'williamhill', 'bet365');

    public function __construct(ContainerInterface $container, OddsProbabilityNormalizer $normalizer)
    {
        $this->container = $container;
        $this->normalizer = $normalizer;
        $this->httpClient = new Client();
    }

    public function findProbabilitiesForFixture(array $fixtureData, Tournament $tournament, Team $homeTeam, Team $awayTeam)
    {
        $apiToken = $this->getApiToken();
        if ($apiToken === '') {
            return null;
        }

        $sportKey = $this->getSportKey($tournament, $apiToken);
        if ($sportKey === null) {
            return null;
        }

        $event = $this->findMatchingEvent($sportKey, $apiToken, $fixtureData, $homeTeam, $awayTeam);
        if ($event === null || !isset($event->id)) {
            return null;
        }

        $oddsEvent = $this->fetchJson('/v4/sports/'.$sportKey.'/events/'.$event->id.'/odds', array(
            'apiKey' => $apiToken,
            'regions' => self::REGION,
            'markets' => self::MARKET,
            'oddsFormat' => self::ODDS_FORMAT,
        ));
        if ($oddsEvent === null) {
            return null;
        }

        return $this->extractSnapshot($oddsEvent, $sportKey, $event->id, $homeTeam, $awayTeam);
    }

    private function getApiToken()
    {
        if (!$this->container->hasParameter('odds_api.token')) {
            return '';
        }

        $token = trim((string) $this->container->getParameter('odds_api.token'));
        if ($token === 'check_the_README_file') {
            return '';
        }

        return $token;
    }

    private function getSportKey(Tournament $tournament, $apiToken)
    {
        $tournamentName = $this->normalizeName($tournament->getName());
        foreach ($this->sportKeyCandidates as $name => $sportKeys) {
            if (strpos($tournamentName, $name) === false) {
                continue;
            }

            foreach ($sportKeys as $sportKey) {
                if ($this->isActiveSportKey($sportKey, $apiToken)) {
                    return $sportKey;
                }
            }
        }

        return null;
    }

    private function isActiveSportKey($sportKey, $apiToken)
    {
        if ($this->activeSportKeys === null) {
            $this->activeSportKeys = array();
            $sports = $this->fetchJson('/v4/sports/', array('apiKey' => $apiToken));
            if (!is_array($sports)) {
                return false;
            }

            foreach ($sports as $sport) {
                if (isset($sport->key)) {
                    $this->activeSportKeys[$sport->key] = true;
                }
            }
        }

        return isset($this->activeSportKeys[$sportKey]);
    }

    private function findMatchingEvent($sportKey, $apiToken, array $fixtureData, Team $homeTeam, Team $awayTeam)
    {
        $matchTime = strtotime($fixtureData['match_local_time']);
        if ($matchTime === false) {
            return null;
        }

        $events = $this->fetchJson('/v4/sports/'.$sportKey.'/events', array(
            'apiKey' => $apiToken,
            'commenceTimeFrom' => gmdate('Y-m-d\TH:i:s\Z', $matchTime - self::MATCH_TOLERANCE_SECONDS),
            'commenceTimeTo' => gmdate('Y-m-d\TH:i:s\Z', $matchTime + self::MATCH_TOLERANCE_SECONDS),
        ));
        if (!is_array($events)) {
            return null;
        }

        $homeName = $this->normalizeName($homeTeam->getName());
        $awayName = $this->normalizeName($awayTeam->getName());
        foreach ($events as $event) {
            if (!isset($event->home_team, $event->away_team, $event->commence_time)) {
                continue;
            }

            if (abs(strtotime($event->commence_time) - $matchTime) > self::MATCH_TOLERANCE_SECONDS) {
                continue;
            }

            if ($this->normalizeName($event->home_team) === $homeName && $this->normalizeName($event->away_team) === $awayName) {
                return $event;
            }
        }

        return null;
    }

    private function extractSnapshot($event, $sportKey, $eventId, Team $homeTeam, Team $awayTeam)
    {
        if (!isset($event->bookmakers) || !is_array($event->bookmakers)) {
            return null;
        }

        $bookmakers = $this->sortBookmakers($event->bookmakers);
        foreach ($bookmakers as $bookmaker) {
            $prices = $this->extractPrices($bookmaker, $homeTeam, $awayTeam);
            if ($prices === null) {
                continue;
            }

            $probabilities = $this->normalizer->normalizeDecimalOdds($prices['home'], $prices['draw'], $prices['away']);
            if ($probabilities === null) {
                continue;
            }

            $bookmakerKey = isset($bookmaker->key) ? $bookmaker->key : 'unknown';
            $probabilities['source'] = 'the_odds_api:'.$sportKey.':'.$eventId.':'.$bookmakerKey.':h2h';

            return $probabilities;
        }

        return null;
    }

    private function sortBookmakers(array $bookmakers)
    {
        usort($bookmakers, function ($left, $right) {
            return $this->bookmakerRank($left) <=> $this->bookmakerRank($right);
        });

        return $bookmakers;
    }

    private function bookmakerRank($bookmaker)
    {
        $key = isset($bookmaker->key) ? $bookmaker->key : '';
        $rank = array_search($key, $this->preferredBookmakers, true);

        return $rank === false ? 100 : $rank;
    }

    private function extractPrices($bookmaker, Team $homeTeam, Team $awayTeam)
    {
        if (!isset($bookmaker->markets) || !is_array($bookmaker->markets)) {
            return null;
        }

        foreach ($bookmaker->markets as $market) {
            if (!isset($market->key, $market->outcomes) || $market->key !== self::MARKET || !is_array($market->outcomes)) {
                continue;
            }

            $prices = array('home' => null, 'draw' => null, 'away' => null);
            $homeName = $this->normalizeName($homeTeam->getName());
            $awayName = $this->normalizeName($awayTeam->getName());
            foreach ($market->outcomes as $outcome) {
                if (!isset($outcome->name, $outcome->price)) {
                    continue;
                }

                $name = $this->normalizeName($outcome->name);
                if ($name === $homeName) {
                    $prices['home'] = $outcome->price;
                } elseif ($name === $awayName) {
                    $prices['away'] = $outcome->price;
                } elseif ($name === 'draw') {
                    $prices['draw'] = $outcome->price;
                }
            }

            if ($prices['home'] !== null && $prices['draw'] !== null && $prices['away'] !== null) {
                return $prices;
            }
        }

        return null;
    }

    private function fetchJson($path, array $query)
    {
        try {
            $response = $this->httpClient->get(self::BASE_URI.$path, array('query' => $query));
        } catch (\Exception $e) {
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return json_decode((string) $response->getBody());
    }

    private function normalizeName($name)
    {
        $name = strtolower((string) $name);
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }
}
