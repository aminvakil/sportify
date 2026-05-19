<?php

namespace Tests\Integration;

use Devlabs\SportifyBundle\Entity\Prediction;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ValidationMappingTest extends KernelTestCase
{
    public function testPredictionGoalConstraintsLoadFromYaml()
    {
        self::bootKernel();

        $prediction = new Prediction();
        $prediction->setHomeGoals('invalid');
        $prediction->setAwayGoals('invalid');

        $violations = self::$kernel->getContainer()
            ->get('validator')
            ->validate($prediction);

        $this->assertCount(2, $violations);
    }
}
