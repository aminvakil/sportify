<?php

namespace Devlabs\SportifyBundle\Command;

use Devlabs\SportifyBundle\Entity\OAuthClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateOAuthClientCommand
 * @package Devlabs\SportifyBundle\Command
 */
class CreateOAuthClientCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oauth:client:create')
            ->setDescription('Create OAuth Client')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Client Name?'
            )
            ->addArgument(
                'redirectUri',
                InputArgument::REQUIRED,
                'Redirect URI?'
            )
            ->addArgument(
                'grantType',
                InputArgument::REQUIRED,
                'Grant Type?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $redirectUri = $input->getArgument('redirectUri');
        $grantType = $input->getArgument('grantType');

        $client = new OAuthClient();
        $client->setName($name);
        $client->setRedirectUris(array($redirectUri));
        $client->setAllowedGrantTypes(array($grantType));

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($client);
        $em->flush();

        $output->writeln(
            sprintf(
                "<info>The client <comment>%s</comment> was created with <comment>%s</comment> as public id and <comment>%s</comment> as secret</info>",
                $client->getName(),
                $client->getPublicId(),
                $client->getSecret()
            )
        );
    }
}
