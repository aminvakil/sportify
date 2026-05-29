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

        $homePercent = (int) round($rawHome / $rawTotal * 100);
        $drawPercent = (int) round($rawDraw / $rawTotal * 100);
        $awayPercent = 100 - $homePercent - $drawPercent;

        return array(
            'home_win_probability_percent' => $homePercent,
            'draw_probability_percent' => $drawPercent,
            'away_win_probability_percent' => $awayPercent,
        );
    }

    private function isValidOdds($odds)
    {
        return $odds !== null && is_numeric($odds) && (float) $odds > 0;
    }
}
