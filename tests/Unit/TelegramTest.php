<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Services\Telegram;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class TelegramTest extends TestCase
{
    public function testSendAdminMessageDoesNotUseMarkdownParseMode()
    {
        $telegram = $this->createTelegram($client = new FakeTelegramHttpClient());

        $response = $telegram->sendAdminMessage('soccer_fifa_world_cup [Mexico]');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('admin-chat', $client->options['form_params']['chat_id']);
        $this->assertSame('soccer_fifa_world_cup [Mexico]', $client->options['form_params']['text']);
        $this->assertArrayNotHasKey('parse_mode', $client->options['form_params']);
    }

    public function testSendAdminMessageDoesNotThrowWhenTelegramTransportFails()
    {
        $telegram = $this->createTelegram(new FailingTelegramHttpClient());

        $response = $telegram->sendAdminMessage('External API failure');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Telegram admin notification failed', $response->getReasonPhrase());
    }

    private function createTelegram($client)
    {
        $container = new Container();
        $container->set('kernel', new FakeTelegramKernel());
        $container->setParameter('telegram.admin_chat_id', 'admin-chat');

        $telegram = new Telegram($container, 'bot-token', 'main-chat');
        $property = new \ReflectionProperty(Telegram::class, 'httpClient');
        $property->setAccessible(true);
        $property->setValue($telegram, $client);

        return $telegram;
    }
}

class FakeTelegramKernel
{
    public function getEnvironment()
    {
        return 'prod';
    }
}

class FakeTelegramHttpClient
{
    public $options;

    public function post($url, array $options)
    {
        $this->options = $options;

        return new Response(200);
    }
}

class FailingTelegramHttpClient
{
    public function post($url, array $options)
    {
        throw new ConnectException('Connection failed', new Request('POST', $url));
    }
}
