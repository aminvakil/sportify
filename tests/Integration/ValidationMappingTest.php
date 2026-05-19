<?php

namespace Tests\Integration;

use Devlabs\SportifyBundle\Entity\Prediction;
use Devlabs\SportifyBundle\Entity\Team;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ValidationMappingTest extends KernelTestCase
{
    public function testTeamUniqueEntityConstraintLoadsFromYaml()
    {
        self::bootKernel();

        $metadata = self::$kernel->getContainer()
            ->get('validator')
            ->getMetadataFor(Team::class);

        $constraints = array_filter($metadata->getConstraints(), function ($constraint) {
            return $constraint instanceof UniqueEntity;
        });

        $this->assertCount(1, $constraints);
        $this->assertSame('name', array_values($constraints)[0]->fields);
    }

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
