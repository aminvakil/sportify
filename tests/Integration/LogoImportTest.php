<?php

namespace Tests\Integration;

require_once __DIR__.'/DatabaseTestCase.php';

if (!defined('WEB_DIRECTORY')) {
    define('WEB_DIRECTORY', __DIR__.'/../../public');
}

use Devlabs\SportifyBundle\Services\DataUpdates\Importer;

class LogoImportTest extends DatabaseTestCase
{
    public function testTeamImportReportsInvalidLogoImage()
    {
        $tournament = $this->createTournament('Logo Cup');
        for ($i = 0; $i < 48; $i++) {
            $this->createTeam('Existing Team '.$i, $tournament);
        }
        $sourcePath = tempnam(sys_get_temp_dir(), 'sportify-bad-team-import-logo-');
        file_put_contents($sourcePath, 'not an image');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Could not decode team logo image');

            $this->createImporter()->importTeams(array(array(
                'team_id' => 10,
                'name' => 'Bad Logo Team',
                'team_logo' => $sourcePath,
            )), $tournament, 'football_data_org');
        } finally {
            if (is_file($sourcePath)) {
                unlink($sourcePath);
            }
        }
    }

    public function testTournamentImportReportsInvalidLogoImage()
    {
        $this->createTournament('Existing Logo Cup');
        $tournament = $this->createTournament('Bad Logo Cup');
        $sourcePath = tempnam(sys_get_temp_dir(), 'sportify-bad-tournament-import-logo-');
        file_put_contents($sourcePath, 'not an image');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Could not decode tournament logo image');

            $this->createImporter()->importTournamentLogo(array(
                'id' => 2000,
                'name' => 'Bad Logo Cup',
                'logo' => $sourcePath,
            ), $tournament);
        } finally {
            if (is_file($sourcePath)) {
                unlink($sourcePath);
            }
        }
    }

    private function createImporter()
    {
        return new Importer(
            self::$kernel->getContainer(),
            $this->em,
            self::$kernel->getContainer()->get('app.scoring_defaults')
        );
    }
}
