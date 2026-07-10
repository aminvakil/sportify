<?php

namespace Devlabs\SportifyBundle\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Telegram
 * @package Devlabs\SportifyBundle\Services
 */
class Telegram
{
    use ContainerAwareTrait;

    private $httpClient;
    private $botToken;
    private $chatId;
    private $logger;

    public function __construct(ContainerInterface $container, $botToken, $chatId, LoggerInterface $logger)
    {
        $this->httpClient = new Client();
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Send a Telegram message.
     */
    public function sendMessage($text)
    {
        return $this->sendToChat($this->chatId, $text);
    }

    /**
     * Send a Telegram message to the admin chat.
     */
    public function sendAdminMessage($text)
    {
        $adminChatId = $this->getAdminChatId();
        if (!$this->isEnabled($adminChatId)) {
            return $this->disabledResponse();
        }

        try {
            $response = $this->sendToChat($adminChatId, $text, null);
        } catch (\Exception $e) {
            $response = new Response(
                500,
                array(),
                null,
                '1.1',
                'Telegram admin notification failed'
            );
            $this->logAdminNotificationFailure($response, $e);

            return $response;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $this->logAdminNotificationFailure($response);
        }

        return $response;
    }

    /**
     * Pin a Telegram message.
     */
    public function pinMessage($messageId)
    {
        if (!$this->isEnabled($this->chatId)) {
            return $this->disabledResponse();
        }

        try {
            return $this->httpClient->post(
                'https://api.telegram.org/bot'.$this->botToken.'/pinChatMessage',
                array(
                    'form_params' => array(
                        'chat_id' => $this->chatId,
                        'message_id' => $messageId,
                    ),
                    'allow_redirects' => false,
                    'timeout' => 5,
                )
            );
        } catch (RequestException $e) {
            return $e->getResponse() ?: new Response(500);
        }
    }

    private function sendToChat($chatId, $text, $parseMode = 'Markdown')
    {
        if (!$this->isEnabled($chatId)) {
            return $this->disabledResponse();
        }

        $formParams = array(
            'chat_id' => $chatId,
            'text' => $text,
        );
        if ($parseMode !== null) {
            $formParams['parse_mode'] = $parseMode;
        }

        try {
            return $this->httpClient->post(
                'https://api.telegram.org/bot'.$this->botToken.'/sendMessage',
                array(
                    'form_params' => $formParams,
                    'allow_redirects' => false,
                    'timeout' => 5,
                )
            );
        } catch (RequestException $e) {
            return $e->getResponse() ?: new Response(500);
        }
    }

    private function isEnabled($chatId)
    {
        $env = $this->container->get('kernel')->getEnvironment();

        return $env === 'prod'
            && $this->botToken
            && $chatId
            && $this->botToken !== 'check_the_README_file'
            && $chatId !== 'check_the_README_file';
    }

    private function logAdminNotificationFailure(Response $response, ?\Exception $exception = null)
    {
        $context = array(
            'status_code' => $response->getStatusCode(),
            'reason' => $response->getReasonPhrase(),
        );
        if ($exception !== null) {
            $context['exception_class'] = get_class($exception);
        }

        $this->logger->warning('telegram_admin_notification_failed', $context);
    }

    private function getAdminChatId()
    {
        if (!$this->container->hasParameter('telegram.admin_chat_id')) {
            return null;
        }

        return $this->container->getParameter('telegram.admin_chat_id');
    }

    private function disabledResponse()
    {
        return new Response(
            400,
            array(),
            null,
            '1.1',
            'Env is not PROD or Telegram config is missing'
        );
    }
}
