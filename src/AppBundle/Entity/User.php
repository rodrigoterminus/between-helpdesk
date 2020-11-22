<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * User
 */
class User extends BaseUser
{
    /**
     * @var integer
     */
    protected $id;

    private $role;

    /**
     * @var bool
     */
    private $deleted = false;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Set role
     *
     * @param string $role
     * @return User
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string 
     */
    public function getRole()
    {
        return $this->role;
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
     * @var string
     */
    private $name;

    /**
     * @var \AppBundle\Entity\Customer
     */
    private $customer;


    /**
     * Set name
     *
     * @param string $name
     * @return User
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
     * Set customer
     *
     * @param \AppBundle\Entity\Customer $customer
     * @return User
     */
    public function setCustomer(\AppBundle\Entity\Customer $customer = null)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get customer
     *
     * @return \AppBundle\Entity\Customer 
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Sets the email.
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->setUsername($email);

        return parent::setEmail($email);
    }

    /**
     * Set the canonical email.
     *
     * @param string $emailCanonical
     * @return User
     */
    public function setEmailCanonical($emailCanonical)
    {
        $this->setUsernameCanonical($emailCanonical);

        return parent::setEmailCanonical($emailCanonical);
    }

    public function isAdmin()
    {
        if ($this->hasRole('ROLE_SUPER_ADMIN') || $this->hasRole('ROLE_ADMIN'))
            return true;
        else
            return false;
    }
    /**
     * @var string
     */
    private $preferences;


    /**
     * Set preferences
     *
     * @param string $preferences
     * @return User
     */
    public function setPreferences($preferences)
    {
        $this->preferences = $preferences;

        return $this;
    }

    /**
     * Get preferences
     *
     * @return string 
     */
    public function getPreferences()
    {
        return $this->preferences;
    }
    
    /**
     * Get a preferences object
     * 
     * @return object
     */
    public function getPreferencesObject()
    {
        return json_decode($this->preferences);
    }
    
    /**
     * Get a user preference
     * 
     * @param $preferencePath
     * 
     * @return boolean
     */
    public function getPreference($preferencePath, $nullWhenNotFound = false)
    {
        $preferences = $this->getPreferencesObject();
        
        $pathArray = explode('.', $preferencePath);
        $path = $preferences;
        
        foreach ($pathArray as $value) {
            if (isset($path->$value)) {
                $path = $path->$value;
            } else {
                if ($nullWhenNotFound === true) {
                    return null;
                } else {
                    return false;
                }
                
            }
        }
        
        return $path;
    }
    /**
     * @var string
     */
    private $notifications;


    /**
     * Set notifications
     *
     * @param string $notifications
     * @return User
     */
    public function setNotifications($notifications)
    {
        $this->notifications = $notifications;

        return $this;
    }

    /**
     * Get notifications
     *
     * @return string 
     */
    public function getNotifications()
    {
        return $this->notifications;
    }
    
    /**
     * 
     * @return Object
     */
    public function getNotificationsArray()
    {
        if ($this->notifications === null) {
            return [];
        } else {
            return json_decode($this->notifications, true);
        }
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     * @return User
     */
    public function setDeleted(bool $deleted): User
    {
        $this->deleted = $deleted;
        return $this;
    }
}
