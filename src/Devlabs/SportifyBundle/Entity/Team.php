<?php

namespace Devlabs\SportifyBundle\Entity;

use Symfony\Component\Routing\RequestContext;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Team
{
    private $id;

    private $name;

    private $tournaments;

    private $predictionsChampion;

    private $tournamentsChampion;

    private $matchesHomeTeam;

    private $matchesAwayTeam;

    /**
     * Team logo
     */
    private $teamLogo;

    /**
     * Temp placeholder for uploaded files
     */
    private $uploadFile;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tournaments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->predictionsChampion = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tournamentsChampion = new \Doctrine\Common\Collections\ArrayCollection();
        $this->matchesHomeTeam = new \Doctrine\Common\Collections\ArrayCollection();
        $this->matchesAwayTeam = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param string $id
     *
     * @return Team
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Team
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add predictionsChampion
     *
     * @param \Devlabs\SportifyBundle\Entity\PredictionChampion $predictionsChampion
     *
     * @return Team
     */
    public function addPredictionsChampion(\Devlabs\SportifyBundle\Entity\PredictionChampion $predictionsChampion)
    {
        $this->predictionsChampion[] = $predictionsChampion;

        return $this;
    }

    /**
     * Remove predictionsChampion
     *
     * @param \Devlabs\SportifyBundle\Entity\PredictionChampion $predictionsChampion
     */
    public function removePredictionsChampion(\Devlabs\SportifyBundle\Entity\PredictionChampion $predictionsChampion)
    {
        $this->predictionsChampion->removeElement($predictionsChampion);
    }

    /**
     * Get predictionsChampion
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPredictionsChampion()
    {
        return $this->predictionsChampion;
    }

    /**
     * Add matchesHomeTeam
     *
     * @param \Devlabs\SportifyBundle\Entity\MatchEntity $matchesHomeTeam
     *
     * @return Team
     */
    public function addMatchesHomeTeam(\Devlabs\SportifyBundle\Entity\MatchEntity $matchesHomeTeam)
    {
        $this->matchesHomeTeam[] = $matchesHomeTeam;

        return $this;
    }

    /**
     * Remove matchesHomeTeam
     *
     * @param \Devlabs\SportifyBundle\Entity\MatchEntity $matchesHomeTeam
     */
    public function removeMatchesHomeTeam(\Devlabs\SportifyBundle\Entity\MatchEntity $matchesHomeTeam)
    {
        $this->matchesHomeTeam->removeElement($matchesHomeTeam);
    }

    /**
     * Get matchesHomeTeam
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMatchesHomeTeam()
    {
        return $this->matchesHomeTeam;
    }

    /**
     * Add matchesAwayTeam
     *
     * @param \Devlabs\SportifyBundle\Entity\MatchEntity $matchesAwayTeam
     *
     * @return Team
     */
    public function addMatchesAwayTeam(\Devlabs\SportifyBundle\Entity\MatchEntity $matchesAwayTeam)
    {
        $this->matchesAwayTeam[] = $matchesAwayTeam;

        return $this;
    }

    /**
     * Remove matchesAwayTeam
     *
     * @param \Devlabs\SportifyBundle\Entity\MatchEntity $matchesAwayTeam
     */
    public function removeMatchesAwayTeam(\Devlabs\SportifyBundle\Entity\MatchEntity $matchesAwayTeam)
    {
        $this->matchesAwayTeam->removeElement($matchesAwayTeam);
    }

    /**
     * Get matchesAwayTeam
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMatchesAwayTeam()
    {
        return $this->matchesAwayTeam;
    }

    /**
     * Add tournamentsChampion
     *
     * @param \Devlabs\SportifyBundle\Entity\Tournament $tournamentsChampion
     *
     * @return Team
     */
    public function addTournamentsChampion(\Devlabs\SportifyBundle\Entity\Tournament $tournamentsChampion)
    {
        $this->tournamentsChampion[] = $tournamentsChampion;

        return $this;
    }

    /**
     * Remove tournamentsChampion
     *
     * @param \Devlabs\SportifyBundle\Entity\Tournament $tournamentsChampion
     */
    public function removeTournamentsChampion(\Devlabs\SportifyBundle\Entity\Tournament $tournamentsChampion)
    {
        $this->tournamentsChampion->removeElement($tournamentsChampion);
    }

    /**
     * Get tournamentsChampion
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTournamentsChampion()
    {
        return $this->tournamentsChampion;
    }

    /**
     * Add tournament
     *
     * @param \Devlabs\SportifyBundle\Entity\Tournament $tournament
     *
     * @return Team
     */
    public function addTournament(\Devlabs\SportifyBundle\Entity\Tournament $tournament)
    {
        $this->tournaments[] = $tournament;

        return $this;
    }

    /**
     * Remove tournament
     *
     * @param \Devlabs\SportifyBundle\Entity\Tournament $tournament
     */
    public function removeTournament(\Devlabs\SportifyBundle\Entity\Tournament $tournament)
    {
        $this->tournaments->removeElement($tournament);
    }

    /**
     * Get tournaments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTournaments()
    {
        return $this->tournaments;
    }

    /**
     * Check if the team already has a logo
     *
     * @return bool $has_logo
     */
    public function hasTeamLogo()
    {
        foreach (array('png', 'jpg', 'svg') as $extension) {
            $file = WEB_DIRECTORY . '/img/team_logos/team_logo_'.$this->id.'.'.$extension;
            if (is_file($file) && $this->isValidLogoFile($file, $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Team Logo
     *
     * @return string $path_to_logo
     */
    public function getTeamLogo()
    {
        foreach (array('png', 'svg', 'jpg') as $extension) {
            $file = WEB_DIRECTORY . '/img/team_logos/team_logo_'.$this->id.'.'.$extension;
            if (is_file($file) && $this->isValidLogoFile($file, $extension)) {
                return 'img/team_logos/team_logo_'.$this->id.'.'.$extension;
            }
        }

        return 'img/default_team_logo.png';
    }

    /**
     * Set Team Logo
     *
     * @return string $path_to_logo
     */
    public function setTeamLogo($filePath = null, $fileExtension = null)
    {
        if (!$filePath)
            return $this;

        /**
         * Skip setting of TeamLogo if image/path is NOT valid,
         * and PHP would throw an exception
         */
        try {
            $file = file_get_contents($filePath);
        }
        catch(\Exception $e) {
            return $this;
        }

        $isSvg = $this->isSvgLogoContents($file, $fileExtension);
        if (!$isSvg) {
            try {
                // create an image manager instance with favored driver
                $manager = new ImageManager();
                $image = $manager->make($file);
            }
            catch(\Exception $e) {
                return $this;
            }
        }

        // delete previous TeamLogo file if NOT the default one
        $currentTeamLogo = $this->getTeamLogo();
        if ($currentTeamLogo !== 'img/default_team_logo.png' && is_file(WEB_DIRECTORY.'/'.$currentTeamLogo)) {
            unlink(WEB_DIRECTORY.'/'.$currentTeamLogo);
        }

        if ($isSvg) {
            file_put_contents(WEB_DIRECTORY . '/img/team_logos/team_logo_' . $this->id . '.svg', $file);
        } else {
            $width = $image->width();
            $height = $image->height();

            if ($width >= $height) {
                $image->resize(300, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $image->resize(null, 300, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $image->save(WEB_DIRECTORY . '/img/team_logos/team_logo_' . $this->id . '.png');
        }

        return $this;
    }

    private function isValidLogoFile($file, $extension)
    {
        if ($extension === 'svg') {
            return $this->isSvgLogoContents(file_get_contents($file), $extension);
        }

        return @getimagesize($file) !== false;
    }

    private function isSvgLogoContents($file, $fileExtension = null)
    {
        if (!in_array($fileExtension, array('svg', 'svg+xml')) && stripos($file, '<svg') === false) {
            return false;
        }

        return stripos($file, '<svg') !== false && stripos($file, '</svg>') !== false;
    }

    public function getUploadFile()
    {
        return $this->uploadFile;
    }

    public function setUploadFile($file)
    {
        $this->uploadFile = $file;

        return $this;
    }

    public function __toString() {
        return "$this->name";
    }
}
