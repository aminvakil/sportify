<?php

namespace Devlabs\SportifyBundle\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
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

    public function __construct(ContainerInterface $container, $botToken, $chatId)
    {
        $this->httpClient = new Client();
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->container = $container;
    }

    /**
     * Send a Telegram message.
     */
    public function sendMessage($text)
    {
        if (!$this->isEnabled()) {
            return $this->disabledResponse();
        }

        try {
            return $this->httpClient->post(
                'https://api.telegram.org/bot'.$this->botToken.'/sendMessage',
                array(
                    'form_params' => array(
                        'chat_id' => $this->chatId,
                        'text' => $text,
                        'parse_mode' => 'Markdown',
                    ),
                    'allow_redirects' => false,
                    'timeout' => 5,
                )
            );
        } catch (RequestException $e) {
            return $e->getResponse() ?: new Response(500);
        }
    }

    /**
     * Pin a Telegram message.
     */
    public function pinMessage($messageId)
    {
        if (!$this->isEnabled()) {
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

    private function isEnabled()
    {
        $env = $this->container->get('kernel')->getEnvironment();

        return $env === 'prod'
            && $this->botToken
            && $this->chatId
            && $this->botToken !== 'check_the_README_file'
            && $this->chatId !== 'check_the_README_file';
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
