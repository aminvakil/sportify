<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Services\Odds\OddsProbabilityNormalizer;
use PHPUnit\Framework\TestCase;

class OddsProbabilityNormalizerTest extends TestCase
{
    public function testNormalizesDecimalOddsToPercent()
    {
        $normalizer = new OddsProbabilityNormalizer();

        $probabilities = $normalizer->normalizeDecimalOdds(2.0, 4.0, 4.0);

        $this->assertSame(array(
            'home_win_probability_percent' => 50,
            'draw_probability_percent' => 25,
            'away_win_probability_percent' => 25,
        ), $probabilities);
        $this->assertSame(100, array_sum($probabilities));
    }

    public function testReturnsNullForIncompleteOrInvalidOdds()
    {
        $normalizer = new OddsProbabilityNormalizer();

        $this->assertNull($normalizer->normalizeDecimalOdds(2.0, null, 4.0));
        $this->assertNull($normalizer->normalizeDecimalOdds(2.0, 0, 4.0));
    }
}
