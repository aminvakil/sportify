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

        list($response, $log) = $this->captureErrorLog(function () use ($telegram) {
            return $telegram->sendAdminMessage('External API failure');
        });

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Telegram admin notification failed', $response->getReasonPhrase());
        $this->assertStringContainsString('Telegram admin notification failed (HTTP 500 Telegram admin notification failed). Exception: GuzzleHttp\\Exception\\ConnectException', $log);
        $this->assertStringNotContainsString('bot-token', $log);
        $this->assertStringNotContainsString('/botbot-token/sendMessage', $log);
    }

    public function testSendAdminMessageLogsNonSuccessResponse()
    {
        $telegram = $this->createTelegram(new UnsuccessfulTelegramHttpClient());

        list($response, $log) = $this->captureErrorLog(function () use ($telegram) {
            return $telegram->sendAdminMessage('External API failure');
        });

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Telegram admin notification failed (HTTP 400 Bad Request).', $log);
    }

    public function testSendAdminMessageDoesNotLogWhenAdminChatIsDisabled()
    {
        $telegram = $this->createTelegram(new FailingTelegramHttpClient(), 'check_the_README_file');

        list($response, $log) = $this->captureErrorLog(function () use ($telegram) {
            return $telegram->sendAdminMessage('External API failure');
        });

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('', $log);
    }

    private function captureErrorLog(callable $callback)
    {
        $logFile = tempnam(sys_get_temp_dir(), 'telegram-error-log-');
        $previousLog = ini_set('error_log', $logFile);

        try {
            $result = $callback();
            $log = file_exists($logFile) ? file_get_contents($logFile) : '';
        } finally {
            ini_set('error_log', $previousLog);
            if (file_exists($logFile)) {
                unlink($logFile);
            }
        }

        return array($result, $log);
    }

    private function createTelegram($client, $adminChatId = 'admin-chat')
    {
        $container = new Container();
        $container->set('kernel', new FakeTelegramKernel());
        $container->setParameter('telegram.admin_chat_id', $adminChatId);

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

class UnsuccessfulTelegramHttpClient
{
    public function post($url, array $options)
    {
        return new Response(400, array(), null, '1.1', 'Bad Request');
    }
}
