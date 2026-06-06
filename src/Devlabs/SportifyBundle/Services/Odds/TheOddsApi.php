<?php

namespace Devlabs\SportifyBundle\Services\Odds;

use Devlabs\SportifyBundle\Entity\OddsProviderTeamMapping;
use Devlabs\SportifyBundle\Entity\Team;
use Devlabs\SportifyBundle\Entity\Tournament;
use Devlabs\SportifyBundle\Services\Telegram;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TheOddsApi
{
    const PROVIDER = 'the_odds_api';
    const REGION = 'eu';
    const MARKET = 'h2h';
    const ODDS_FORMAT = 'decimal';
    const MATCH_TOLERANCE_SECONDS = 600;
    const MAX_ODDS_UNAVAILABLE_ALERT_FIXTURES = 10;

    private $container;
    private $httpClient;
    private $baseUri;
    private $normalizer;
    private $telegram;
    private $entityManager;
    private $activeSportKeys;
    private $lastEventMatchingReason;
    private $oddsUnavailableNotifications = array();

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

    public function __construct(ContainerInterface $container, OddsProbabilityNormalizer $normalizer, $baseUri, ?ClientInterface $httpClient = null, ?Telegram $telegram = null, ?EntityManagerInterface $entityManager = null)
    {
        $this->container = $container;
        $this->normalizer = $normalizer;
        $this->baseUri = rtrim((string) $baseUri, '/');
        $this->httpClient = $httpClient ?: new Client();
        $this->telegram = $telegram;
        $this->entityManager = $entityManager;
    }

    public function findProbabilitiesForFixture(array $fixtureData, Tournament $tournament, Team $homeTeam, Team $awayTeam)
    {
        try {
            $apiToken = $this->getApiToken();
            if ($apiToken === '') {
                $this->notifyOddsUnavailable($fixtureData, $tournament, $homeTeam, $awayTeam, 'The Odds API token is not configured.');

                return null;
            }

            $sportKey = $this->getSportKey($tournament, $apiToken);
            if ($sportKey === null) {
                $this->notifyOddsUnavailable($fixtureData, $tournament, $homeTeam, $awayTeam, 'No active The Odds API sport key matched the tournament.');

                return null;
            }

            $event = $this->findMatchingEvent($sportKey, $apiToken, $fixtureData, $tournament, $homeTeam, $awayTeam);
            if ($event === null || !isset($event->id)) {
                $this->notifyOddsUnavailable($fixtureData, $tournament, $homeTeam, $awayTeam, $this->lastEventMatchingReason ?: 'No matching The Odds API event was found.');

                return null;
            }

            $oddsEvent = $this->fetchJson('/v4/sports/'.$sportKey.'/events/'.$event->id.'/odds', array(
                'apiKey' => $apiToken,
                'regions' => self::REGION,
                'markets' => self::MARKET,
                'oddsFormat' => self::ODDS_FORMAT,
            ));
            if ($oddsEvent === null) {
                $this->notifyOddsUnavailable($fixtureData, $tournament, $homeTeam, $awayTeam, 'The Odds API odds response was empty.');

                return null;
            }

            $snapshot = $this->extractSnapshot($oddsEvent, $sportKey, $event->id, $event->home_team, $event->away_team);
            if ($snapshot === null) {
                $this->notifyOddsUnavailable($fixtureData, $tournament, $homeTeam, $awayTeam, 'No complete home/draw/away odds snapshot was found.');
            }

            return $snapshot;
        } catch (\RuntimeException $e) {
            $this->notifyOddsUnavailable($fixtureData, $tournament, $homeTeam, $awayTeam, $e->getMessage());

            throw $e;
        }
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

    private function findMatchingEvent($sportKey, $apiToken, array $fixtureData, Tournament $tournament, Team $homeTeam, Team $awayTeam)
    {
        $this->lastEventMatchingReason = 'No matching The Odds API event was found.';
        $matchTime = strtotime($fixtureData['match_local_time']);
        if ($matchTime === false) {
            $this->lastEventMatchingReason = 'Football-Data fixture kickoff time could not be parsed.';

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

        $matches = array();
        $reasons = array();
        foreach ($events as $event) {
            if (!isset($event->home_team, $event->away_team, $event->commence_time)) {
                continue;
            }

            if (abs(strtotime($event->commence_time) - $matchTime) > self::MATCH_TOLERANCE_SECONDS) {
                continue;
            }

            $teamMatch = $this->matchEventTeams($event, $fixtureData, $tournament, $homeTeam, $awayTeam);
            if ($teamMatch['matched']) {
                $matches[] = array('event' => $event, 'mappings' => $teamMatch['mappings']);
            } elseif ($teamMatch['reason'] !== '') {
                $reasons[] = $teamMatch['reason'];
            }
        }

        if (count($matches) === 1) {
            $this->persistLearnedMappings($matches[0]['mappings'], $tournament);

            return $matches[0]['event'];
        }

        if (count($matches) > 1) {
            $this->lastEventMatchingReason = 'Ambiguous The Odds API event match: multiple events matched the Football-Data kickoff and team metadata.';

            return null;
        }

        if ($reasons) {
            $this->lastEventMatchingReason = 'No safely matched The Odds API event was found. '.$this->summarizeReasons($reasons);
        }

        return null;
    }

    private function matchEventTeams($event, array $fixtureData, Tournament $tournament, Team $homeTeam, Team $awayTeam)
    {
        $homeMatch = $this->matchProviderTeamNameToFixtureSide($event->home_team, $fixtureData, $tournament, $homeTeam, $awayTeam, 'home');
        $awayMatch = $this->matchProviderTeamNameToFixtureSide($event->away_team, $fixtureData, $tournament, $homeTeam, $awayTeam, 'away');

        if ($homeMatch['matched'] && $awayMatch['matched']) {
            return array(
                'matched' => true,
                'mappings' => array_merge($homeMatch['mappings'], $awayMatch['mappings']),
                'reason' => '',
            );
        }

        $reason = 'Event "'.$event->home_team.' vs '.$event->away_team.'": ';
        $sideReasons = array();
        if (!$homeMatch['matched'] && $homeMatch['reason'] !== '') {
            $sideReasons[] = $homeMatch['reason'];
        }
        if (!$awayMatch['matched'] && $awayMatch['reason'] !== '') {
            $sideReasons[] = $awayMatch['reason'];
        }

        return array('matched' => false, 'mappings' => array(), 'reason' => $reason.implode(' ', $sideReasons));
    }

    private function matchProviderTeamNameToFixtureSide($providerTeamName, array $fixtureData, Tournament $tournament, Team $homeTeam, Team $awayTeam, $expectedSide)
    {
        $expectedTeam = $expectedSide === 'home' ? $homeTeam : $awayTeam;
        $mappedTeamId = $this->getMappedTeamId($tournament, $providerTeamName);
        if ($mappedTeamId !== null) {
            if ((int) $mappedTeamId === (int) $expectedTeam->getId()) {
                return array('matched' => true, 'mappings' => array(), 'reason' => '');
            }

            return array(
                'matched' => false,
                'mappings' => array(),
                'reason' => 'Provider team "'.$providerTeamName.'" is already mapped to "'.$this->getTeamNameById($mappedTeamId).'", not fixture '.$expectedSide.' team "'.$expectedTeam->getName().'".',
            );
        }

        $normalizedProviderTeamName = $this->normalizeName($providerTeamName);
        $matchingSides = array();
        foreach (array('home' => $homeTeam, 'away' => $awayTeam) as $side => $team) {
            $candidateNames = $this->getTeamCandidateNames($fixtureData, $side, $team);
            if (isset($candidateNames[$normalizedProviderTeamName])) {
                $matchingSides[] = $side;
            }
        }

        if (count($matchingSides) > 1) {
            return array(
                'matched' => false,
                'mappings' => array(),
                'reason' => 'Provider team "'.$providerTeamName.'" ambiguously matches both Football-Data fixture teams.',
            );
        }

        if (!$matchingSides) {
            return array(
                'matched' => false,
                'mappings' => array(),
                'reason' => 'Missing The Odds API team mapping for provider team "'.$providerTeamName.'".',
            );
        }

        if ($matchingSides[0] !== $expectedSide) {
            $matchedTeam = $matchingSides[0] === 'home' ? $homeTeam : $awayTeam;

            return array(
                'matched' => false,
                'mappings' => array(),
                'reason' => 'Provider team "'.$providerTeamName.'" matches Football-Data metadata for "'.$matchedTeam->getName().'", not fixture '.$expectedSide.' team "'.$expectedTeam->getName().'".',
            );
        }

        $mappings = array();
        if ($tournament->getId() !== null && $expectedTeam->getId() !== null) {
            $mappings[] = array(
                'provider_team_name' => $providerTeamName,
                'normalized_provider_team_name' => $normalizedProviderTeamName,
                'team_id' => $expectedTeam->getId(),
            );
        }

        return array('matched' => true, 'mappings' => $mappings, 'reason' => '');
    }

    private function getMappedTeamId(Tournament $tournament, $providerTeamName)
    {
        $mapping = $this->getProviderTeamMapping($tournament, $providerTeamName);

        return $mapping === null ? null : $mapping->getTeamId();
    }

    private function getProviderTeamMapping(Tournament $tournament, $providerTeamName)
    {
        if ($this->entityManager === null || $tournament->getId() === null) {
            return null;
        }

        return $this->entityManager->getRepository(OddsProviderTeamMapping::class)
            ->getByTournamentProviderAndNormalizedName($tournament->getId(), self::PROVIDER, $this->normalizeName($providerTeamName));
    }

    private function persistLearnedMappings(array $mappings, Tournament $tournament)
    {
        if ($this->entityManager === null || $tournament->getId() === null) {
            return;
        }

        $persisted = false;
        foreach ($mappings as $mappingData) {
            $existingMapping = $this->entityManager->getRepository(OddsProviderTeamMapping::class)
                ->getByTournamentProviderAndNormalizedName($tournament->getId(), self::PROVIDER, $mappingData['normalized_provider_team_name']);
            if ($existingMapping !== null) {
                continue;
            }

            $mapping = new OddsProviderTeamMapping();
            $mapping->setTournamentId($tournament->getId());
            $mapping->setProvider(self::PROVIDER);
            $mapping->setProviderTeamName($mappingData['provider_team_name']);
            $mapping->setNormalizedProviderTeamName($mappingData['normalized_provider_team_name']);
            $mapping->setTeamId($mappingData['team_id']);

            $this->entityManager->persist($mapping);
            $persisted = true;
        }

        if ($persisted) {
            $this->entityManager->flush();
        }
    }

    private function getTeamNameById($teamId)
    {
        if ($this->entityManager === null) {
            return '#'.$teamId;
        }

        $team = $this->entityManager->getRepository(Team::class)->find($teamId);

        return $team === null ? '#'.$teamId : $team->getName();
    }

    private function summarizeReasons(array $reasons)
    {
        $uniqueReasons = array_values(array_unique($reasons));
        $text = implode(' ', array_slice($uniqueReasons, 0, 3));
        if (count($uniqueReasons) > 3) {
            $text .= ' ...and '.(count($uniqueReasons) - 3).' more candidate event(s).';
        }

        return $text;
    }

    private function getTeamCandidateNames(array $fixtureData, $side, Team $team)
    {
        $names = array($team->getName());
        foreach (array('name', 'short_name', 'tla', 'area_name', 'area_code') as $field) {
            $key = $side.'_team_'.$field;
            if (isset($fixtureData[$key]) && $fixtureData[$key]) {
                $names[] = $fixtureData[$key];
            }
        }

        $candidateNames = array();
        foreach ($names as $name) {
            $normalizedName = $this->normalizeName($name);
            if ($normalizedName !== '') {
                $candidateNames[$normalizedName] = $name;
            }
        }

        return $candidateNames;
    }

    private function extractSnapshot($event, $sportKey, $eventId, $providerHomeTeamName, $providerAwayTeamName)
    {
        if (!isset($event->bookmakers) || !is_array($event->bookmakers)) {
            return null;
        }

        $bookmakers = $this->sortBookmakers($event->bookmakers);
        foreach ($bookmakers as $bookmaker) {
            $prices = $this->extractPrices($bookmaker, $providerHomeTeamName, $providerAwayTeamName);
            if ($prices === null) {
                continue;
            }

            $probabilities = $this->normalizer->normalizeDecimalOdds($prices['home'], $prices['draw'], $prices['away']);
            if ($probabilities === null) {
                continue;
            }

            $bookmakerKey = isset($bookmaker->key) ? $bookmaker->key : 'unknown';
            $probabilities['source'] = self::PROVIDER.':'.$sportKey.':'.$eventId.':'.$bookmakerKey.':h2h';

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

    private function extractPrices($bookmaker, $providerHomeTeamName, $providerAwayTeamName)
    {
        if (!isset($bookmaker->markets) || !is_array($bookmaker->markets)) {
            return null;
        }

        foreach ($bookmaker->markets as $market) {
            if (!isset($market->key, $market->outcomes) || $market->key !== self::MARKET || !is_array($market->outcomes)) {
                continue;
            }

            $prices = array('home' => null, 'draw' => null, 'away' => null);
            $homeName = $this->normalizeName($providerHomeTeamName);
            $awayName = $this->normalizeName($providerAwayTeamName);
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

    public function flushOddsUnavailableNotifications()
    {
        if (!$this->oddsUnavailableNotifications) {
            return null;
        }

        $notifications = $this->oddsUnavailableNotifications;
        $this->oddsUnavailableNotifications = array();
        if ($this->telegram === null) {
            return null;
        }

        $count = count($notifications);
        $text = $count === 1
            ? "Fixture skipped because odds snapshot is unavailable."
            : $count." fixtures skipped because odds snapshots are unavailable.";

        $shownNotifications = array_slice($notifications, 0, self::MAX_ODDS_UNAVAILABLE_ALERT_FIXTURES);
        foreach ($shownNotifications as $notification) {
            $text .= "\n\nTournament: ".$notification['tournament']
                ."\nMatch: ".$notification['match']
                ."\nKickoff: ".$notification['kickoff']
                ."\nFootball-Data match id: ".$notification['match_id']
                ."\nReason: ".$notification['reason'];
        }

        if ($count > self::MAX_ODDS_UNAVAILABLE_ALERT_FIXTURES) {
            $text .= "\n\n...and ".($count - self::MAX_ODDS_UNAVAILABLE_ALERT_FIXTURES)." more.";
        }

        return $this->telegram->sendAdminMessage($text);
    }

    private function notifyOddsUnavailable(array $fixtureData, Tournament $tournament, Team $homeTeam, Team $awayTeam, $reason)
    {
        $this->oddsUnavailableNotifications[] = array(
            'tournament' => $tournament->getName(),
            'match' => $homeTeam->getName().' vs '.$awayTeam->getName(),
            'kickoff' => isset($fixtureData['match_local_time']) ? $fixtureData['match_local_time'] : 'unknown',
            'match_id' => isset($fixtureData['match_id']) ? $fixtureData['match_id'] : 'unknown',
            'reason' => $reason,
        );
    }

    private function fetchJson($path, array $query)
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUri.$path, array('query' => $query));
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->getResponse() !== null) {
                throw new \RuntimeException(sprintf(
                    'The Odds API request failed for "%s" (HTTP %d %s).',
                    $path,
                    $e->getResponse()->getStatusCode(),
                    $e->getResponse()->getReasonPhrase()
                ), 0, $e);
            }

            throw new \RuntimeException(sprintf('The Odds API request failed for "%s".', $path), 0, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('The Odds API request failed for "%s".', $path), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(sprintf(
                'The Odds API request failed for "%s" (HTTP %d %s).',
                $path,
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
        }

        $data = json_decode((string) $response->getBody());
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(sprintf('The Odds API returned invalid JSON for "%s".', $path));
        }

        return $data;
    }

    private function normalizeName($name)
    {
        $name = strtolower((string) $name);
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name));
    }
}
