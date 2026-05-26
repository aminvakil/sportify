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
        $env = $this->container->get('kernel')->getEnvironment();

        if ($env !== 'prod' || !$this->botToken || !$this->chatId || $this->botToken === 'check_the_README_file' || $this->chatId === 'check_the_README_file') {
            return new Response(
                400,
                array(),
                null,
                '1.1',
                'Env is not PROD or Telegram config is missing'
            );
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
}
