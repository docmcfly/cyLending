<?php
namespace Cylancer\CyLending\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 *
 * This file is part of the "lending" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */
class FrontendUser extends AbstractEntity
{

    /** @var ObjectStorage<FrontendUserGroup> */
    protected $usergroup;

    protected string $username = '';

    protected ?string $firstName = '';

    protected ?string $lastName = '';

    protected ?string $email = '';
    /**
     * Constructs a new Front-End User
     */
    public function __construct()
    {
        $this->usergroup = new ObjectStorage();
    }

    public function initializeObject()
    {
        $this->usergroup = $this->usergroup ?? new ObjectStorage();
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }


    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName($firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName($lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }


    public function setUsergroup(ObjectStorage $usergroup): void
    {
        $this->usergroup = $usergroup;
    }

    public function addUsergroup(FrontendUserGroup $usergroup): void
    {
        $this->usergroup->attach($usergroup);
    }

    public function removeUsergroup(FrontendUserGroup $usergroup): void
    {
        $this->usergroup->detach($usergroup);
    }

    public function getUsergroup(): ObjectStorage
    {
        return $this->usergroup;
    }

}