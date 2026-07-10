<?php

namespace Tests\Unit;

use Devlabs\SportifyBundle\Services\Telegram;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\DependencyInjection\Container;

class TelegramTest extends TestCase
{
    private $logger;

    protected function setUp(): void
    {
        $this->logger = new FakeTelegramLogger();
    }

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
        $this->assertSame(array(array(
            'level' => 'warning',
            'message' => 'telegram_admin_notification_failed',
            'context' => array(
                'status_code' => 500,
                'reason' => 'Telegram admin notification failed',
                'exception_class' => ConnectException::class,
            ),
        )), $this->logger->records);
    }

    public function testSendAdminMessageLogsNonSuccessResponse()
    {
        $telegram = $this->createTelegram(new UnsuccessfulTelegramHttpClient());

        $response = $telegram->sendAdminMessage('External API failure');

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(array(array(
            'level' => 'warning',
            'message' => 'telegram_admin_notification_failed',
            'context' => array(
                'status_code' => 400,
                'reason' => 'Bad Request',
            ),
        )), $this->logger->records);
    }

    public function testSendAdminMessageDoesNotLogWhenAdminChatIsDisabled()
    {
        $telegram = $this->createTelegram(new FailingTelegramHttpClient(), 'check_the_README_file');

        $response = $telegram->sendAdminMessage('External API failure');

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(array(), $this->logger->records);
    }

    private function createTelegram($client, $adminChatId = 'admin-chat')
    {
        $container = new Container();
        $container->set('kernel', new FakeTelegramKernel());
        $container->setParameter('telegram.admin_chat_id', $adminChatId);

        $telegram = new Telegram($container, 'bot-token', 'main-chat', $this->logger);
        $property = new \ReflectionProperty(Telegram::class, 'httpClient');
        $property->setAccessible(true);
        $property->setValue($telegram, $client);

        return $telegram;
    }
}

class FakeTelegramLogger extends AbstractLogger
{
    public $records = array();

    public function log($level, $message, array $context = array()): void
    {
        $this->records[] = array(
            'level' => $level,
            'message' => $message,
            'context' => $context,
        );
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
