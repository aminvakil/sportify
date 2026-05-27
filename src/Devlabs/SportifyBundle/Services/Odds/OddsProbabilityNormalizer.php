<?php

namespace Devlabs\SportifyBundle\Services\Odds;

class OddsProbabilityNormalizer
{
    public function normalizeDecimalOdds($homeOdds, $drawOdds, $awayOdds)
    {
        if (!$this->isValidOdds($homeOdds) || !$this->isValidOdds($drawOdds) || !$this->isValidOdds($awayOdds)) {
            return null;
        }

        $rawHome = 1 / (float) $homeOdds;
        $rawDraw = 1 / (float) $drawOdds;
        $rawAway = 1 / (float) $awayOdds;
        $rawTotal = $rawHome + $rawDraw + $rawAway;

        if ($rawTotal <= 0) {
            return null;
        }

        $homeBps = (int) round($rawHome / $rawTotal * 10000);
        $drawBps = (int) round($rawDraw / $rawTotal * 10000);
        $awayBps = 10000 - $homeBps - $drawBps;

        return array(
            'home_win_probability_bps' => $homeBps,
            'draw_probability_bps' => $drawBps,
            'away_win_probability_bps' => $awayBps,
        );
    }

    private function isValidOdds($odds)
    {
        return $odds !== null && is_numeric($odds) && (float) $odds > 0;
    }
}
