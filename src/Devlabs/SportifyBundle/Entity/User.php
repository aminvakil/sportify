<?php

namespace Devlabs\SportifyBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="Devlabs\SportifyBundle\Entity\UserRepository")
 * @ORM\Table(name="users")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=180)
     */
    protected $username;

    /**
     * @ORM\Column(type="string", length=180, name="username_canonical", unique=true)
     */
    protected $usernameCanonical;

    /**
     * @ORM\Column(type="string", length=180)
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=180, name="email_canonical", unique=true)
     */
    protected $emailCanonical;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $salt;

    /**
     * @ORM\Column(type="string")
     */
    protected $password;

    protected $plainPassword;

    /**
     * @ORM\Column(type="datetime", name="last_login", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\Column(type="string", length=180, name="confirmation_token", unique=true, nullable=true)
     */
    protected $confirmationToken;

    /**
     * @ORM\Column(type="datetime", name="password_requested_at", nullable=true)
     */
    protected $passwordRequestedAt;

    /**
     * @ORM\Column(type="array")
     */
    protected $roles;

    /**
     * @ORM\Column(type="string", length=100, name="slack_username")
     */
    private $slackUsername;

    /**
     * @ORM\OneToMany(targetEntity="Score" , mappedBy="userId" , cascade={"all"})
     */
    private $scores;

    /**
     * @ORM\OneToMany(targetEntity="Prediction" , mappedBy="userId" , cascade={"all"})
     */
    private $predictions;

    /**
     * @ORM\OneToMany(targetEntity="PredictionChampion" , mappedBy="userId" , cascade={"all"})
     */
    private $predictionsChampion;

    public function __construct()
    {
        $this->enabled = false;
        $this->roles = array();
        $this->scores = new ArrayCollection();
        $this->predictions = new ArrayCollection();
        $this->predictionsChampion = new ArrayCollection();
        $this->slackUsername = 'slack_username';
    }

    public function __toString()
    {
        return (string) $this->getUsername();
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        return $this->getPassword() === $user->getPassword()
            && $this->getSalt() === $user->getSalt()
            && $this->getUsername() === $user->getUsername()
            && $this->isEnabled() === $user->isEnabled()
            && count($this->getRoles()) === count($user->getRoles())
            && count($this->getRoles()) === count(array_intersect($this->getRoles(), $user->getRoles()));
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getUsernameCanonical()
    {
        return $this->usernameCanonical;
    }

    public function setUsernameCanonical($usernameCanonical)
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    public function setEmailCanonical($emailCanonical)
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;

        return $this;
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    public function setLastLogin(\DateTime $lastLogin = null)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(\DateTime $passwordRequestedAt = null)
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    public function isPasswordRequestNonExpired($ttl)
    {
        return $this->passwordRequestedAt instanceof \DateTime
            && $this->passwordRequestedAt->getTimestamp() + $ttl > time();
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = self::ROLE_DEFAULT;

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles)
    {
        $this->roles = array();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function addRole($role)
    {
        $role = strtoupper($role);

        if ($role !== self::ROLE_DEFAULT && !in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function isSuperAdmin()
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function setSuperAdmin($boolean)
    {
        if ($boolean) {
            $this->addRole(self::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(self::ROLE_SUPER_ADMIN);
        }

        return $this;
    }

    /**
     * Add score
     *
     * @param \Devlabs\SportifyBundle\Entity\Score $score
     *
     * @return User
     */
    public function addScore(\Devlabs\SportifyBundle\Entity\Score $score)
    {
        $this->scores[] = $score;

        return $this;
    }

    /**
     * Remove score
     *
     * @param \Devlabs\SportifyBundle\Entity\Score $score
     */
    public function removeScore(\Devlabs\SportifyBundle\Entity\Score $score)
    {
        $this->scores->removeElement($score);
    }

    /**
     * Get scores
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getScores()
    {
        return $this->scores;
    }

    /**
     * Add prediction
     *
     * @param \Devlabs\SportifyBundle\Entity\Prediction $prediction
     *
     * @return User
     */
    public function addPrediction(\Devlabs\SportifyBundle\Entity\Prediction $prediction)
    {
        $this->predictions[] = $prediction;

        return $this;
    }

    /**
     * Remove prediction
     *
     * @param \Devlabs\SportifyBundle\Entity\Prediction $prediction
     */
    public function removePrediction(\Devlabs\SportifyBundle\Entity\Prediction $prediction)
    {
        $this->predictions->removeElement($prediction);
    }

    /**
     * Get predictions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPredictions()
    {
        return $this->predictions;
    }

    /**
     * Add predictionsChampion
     *
     * @param \Devlabs\SportifyBundle\Entity\PredictionChampion $predictionsChampion
     *
     * @return User
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
     * Set slackUsername
     *
     * @param string $slackUsername
     *
     * @return User
     */
    public function setSlackUsername($slackUsername)
    {
        $this->slackUsername = $slackUsername;

        return $this;
    }

    /**
     * Get slackUsername
     *
     * @return string
     */
    public function getSlackUsername()
    {
        return $this->slackUsername;
    }
}
