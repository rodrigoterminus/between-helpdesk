<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Customer
 */
class Customer
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var boolean
     */
    private $activated;

    /**
     * @var bool
     */
    private $deleted = false;

    /**
     * @var ArrayCollection
     */
    private $projects;

    /**
     * @var ArrayCollection
     */
    private $tickets;

    /**
     * @var ArrayCollection
     */
    private $users;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->projects = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Customer
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
     * Set activated
     *
     * @param boolean $activated
     * @return Customer
     */
    public function setActivated($activated)
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * Get activated
     *
     * @return boolean 
     */
    public function getActivated()
    {
        return $this->activated;
    }

    public function getTickets()
    {
        return $this->tickets;
    }

    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @return bool
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     * @return Customer
     */
    public function setDeleted(bool $deleted)
    {
        $this->deleted = $deleted;
        return $this;
    }
}
