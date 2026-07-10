<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class ProductionLoggingConfigurationTest extends TestCase
{
    public function testPredictionWarningsBypassErrorTriggeredHandler()
    {
        $projectDir = dirname(__DIR__, 2);
        $baseConfig = Yaml::parseFile($projectDir.'/config/packages/monolog.yml');
        $prodConfig = Yaml::parseFile($projectDir.'/config/packages/prod/monolog.yml');
        $handlers = $prodConfig['monolog']['handlers'];

        $this->assertContains('prediction', $baseConfig['monolog']['channels']);
        $this->assertSame('stream', $handlers['prediction_rejections']['type']);
        $this->assertSame('warning', $handlers['prediction_rejections']['level']);
        $this->assertSame(array('prediction'), $handlers['prediction_rejections']['channels']);
        $this->assertContains('!prediction', $handlers['main']['channels']);
    }
}
