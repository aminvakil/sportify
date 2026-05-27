<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Command\DataUpdateCommand;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

class DataUpdateCommandTest extends TestCase
{
    public function testSendsFixtureUpdateNotificationsThroughConfiguredServices()
    {
        $container = new Container();
        $slack = new FakeDataUpdateSlack();
        $telegram = new FakeDataUpdateTelegram();

        $this->configureContainer($container, $slack, $telegram);

        $tester = new CommandTester(new DataUpdateCommand($container));
        $tester->execute(array(
            'type' => 'matches-fixtures',
            'days' => 3,
        ));

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame('Match fixtures added for next 3 days. 2 fixture(s) added.', $slack->text);
        $this->assertCount(1, $telegram->messages);
        $this->assertStringContainsString($slack->text, $telegram->messages[0]);
        $this->assertSame(array(321), $telegram->pinnedMessageIds);
    }

    public function testCanDisableTelegramMessagePinning()
    {
        $container = new Container();
        $slack = new FakeDataUpdateSlack();
        $telegram = new FakeDataUpdateTelegram();

        $this->configureContainer($container, $slack, $telegram);
        $container->setParameter('telegram.pin_messages', false);

        $tester = new CommandTester(new DataUpdateCommand($container));
        $tester->execute(array(
            'type' => 'matches-fixtures',
            'days' => 3,
        ));

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertCount(1, $telegram->messages);
        $this->assertSame(array(), $telegram->pinnedMessageIds);
    }

    private function configureContainer(Container $container, FakeDataUpdateSlack $slack, FakeDataUpdateTelegram $telegram)
    {
        $container->set('app.data_updates.manager', new FakeDataUpdatesManager());
        $container->set('app.slack', $slack);
        $container->set('app.telegram', $telegram);
    }
}

class FakeDataUpdatesManager
{
    public function updateFixtures($dateFrom, $dateTo)
    {
        return array(
            'total_added' => 2,
            'total_updated' => 0,
        );
    }
}

class FakeDataUpdateSlack
{
    public $text;

    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    public function post()
    {
    }
}

class FakeDataUpdateTelegram
{
    public $messages = array();
    public $pinnedMessageIds = array();

    public function sendMessage($text)
    {
        $this->messages[] = $text;

        return new Response(200, array(), json_encode(array(
            'result' => array(
                'message_id' => 321,
            ),
        )));
    }

    public function pinMessage($messageId)
    {
        $this->pinnedMessageIds[] = $messageId;

        return new Response(200);
    }
}
