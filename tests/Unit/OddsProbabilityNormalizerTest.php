<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Services\Odds\OddsProbabilityNormalizer;
use PHPUnit\Framework\TestCase;

class OddsProbabilityNormalizerTest extends TestCase
{
    public function testNormalizesDecimalOddsToBasisPoints()
    {
        $normalizer = new OddsProbabilityNormalizer();

        $probabilities = $normalizer->normalizeDecimalOdds(2.0, 4.0, 4.0);

        $this->assertSame(array(
            'home_win_probability_bps' => 5000,
            'draw_probability_bps' => 2500,
            'away_win_probability_bps' => 2500,
        ), $probabilities);
        $this->assertSame(10000, array_sum($probabilities));
    }

    public function testReturnsNullForIncompleteOrInvalidOdds()
    {
        $normalizer = new OddsProbabilityNormalizer();

        $this->assertNull($normalizer->normalizeDecimalOdds(2.0, null, 4.0));
        $this->assertNull($normalizer->normalizeDecimalOdds(2.0, 0, 4.0));
    }
}
