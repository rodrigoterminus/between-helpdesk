<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Statistic
 */
class Statistic
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $waitingTimeEver;

    /**
     * @var string
     */
    private $waitingTimeMonth;

    /**
     * @var string
     */
    private $ticketTimeEver;

    /**
     * @var string
     */
    private $ticketTimeMonth;

    /**
     * @var string
     */
    private $serviceTimeEver;

    /**
     * @var string
     */
    private $serviceTimeMonth;


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
     * Set waitingTimeEver
     *
     * @param string $waitingTimeEver
     * @return Statistic
     */
    public function setWaitingTimeEver($waitingTimeEver)
    {
        $this->waitingTimeEver = $waitingTimeEver;

        return $this;
    }

    /**
     * Get waitingTimeEver
     *
     * @return string 
     */
    public function getWaitingTimeEver()
    {
        return $this->waitingTimeEver;
    }

    /**
     * Set waitingTimeMonth
     *
     * @param string $waitingTimeMonth
     * @return Statistic
     */
    public function setWaitingTimeMonth($waitingTimeMonth)
    {
        $this->waitingTimeMonth = $waitingTimeMonth;

        return $this;
    }

    /**
     * Get waitingTimeMonth
     *
     * @return string 
     */
    public function getWaitingTimeMonth()
    {
        return $this->waitingTimeMonth;
    }

    /**
     * Set ticketTimeEver
     *
     * @param string $ticketTimeEver
     * @return Statistic
     */
    public function setTicketTimeEver($ticketTimeEver)
    {
        $this->ticketTimeEver = $ticketTimeEver;

        return $this;
    }

    /**
     * Get ticketTimeEver
     *
     * @return string 
     */
    public function getTicketTimeEver()
    {
        return $this->ticketTimeEver;
    }

    /**
     * Set ticketTimeMonth
     *
     * @param string $ticketTimeMonth
     * @return Statistic
     */
    public function setTicketTimeMonth($ticketTimeMonth)
    {
        $this->ticketTimeMonth = $ticketTimeMonth;

        return $this;
    }

    /**
     * Get ticketTimeMonth
     *
     * @return string 
     */
    public function getTicketTimeMonth()
    {
        return $this->ticketTimeMonth;
    }

    /**
     * Set serviceTimeEver
     *
     * @param string $serviceTimeEver
     * @return Statistic
     */
    public function setServiceTimeEver($serviceTimeEver)
    {
        $this->serviceTimeEver = $serviceTimeEver;

        return $this;
    }

    /**
     * Get serviceTimeEver
     *
     * @return string 
     */
    public function getServiceTimeEver()
    {
        return $this->serviceTimeEver;
    }

    /**
     * Set serviceTimeMonth
     *
     * @param string $serviceTimeMonth
     * @return Statistic
     */
    public function setServiceTimeMonth($serviceTimeMonth)
    {
        $this->serviceTimeMonth = $serviceTimeMonth;

        return $this;
    }

    /**
     * Get serviceTimeMonth
     *
     * @return string 
     */
    public function getServiceTimeMonth()
    {
        return $this->serviceTimeMonth;
    }
    
    public function getTimeProperties()
    {
        return [
            'waitingTimeEver',
            'waitingTimeMonth',
            'serviceTimeEver',
            'serviceTimeMonth',
            'ticketTimeEver',
            'ticketTimeMonth',
        ];
    }
    /**
     * @var string
     */
    private $ticketsTotal;

    /**
     * @var string
     */
    private $ticketsLastMonth;

    /**
     * @var string
     */
    private $ticketsUntilLastMonth;

    /**
     * @var string
     */
    private $ticketsThisMonth;


    /**
     * Set ticketsTotal
     *
     * @param string $ticketsTotal
     * @return Statistic
     */
    public function setTicketsTotal($ticketsTotal)
    {
        $this->ticketsTotal = $ticketsTotal;

        return $this;
    }

    /**
     * Get ticketsTotal
     *
     * @return string 
     */
    public function getTicketsTotal()
    {
        return $this->ticketsTotal;
    }

    /**
     * Set ticketsLastMonth
     *
     * @param string $ticketsLastMonth
     * @return Statistic
     */
    public function setTicketsLastMonth($ticketsLastMonth)
    {
        $this->ticketsLastMonth = $ticketsLastMonth;

        return $this;
    }

    /**
     * Get ticketsLastMonth
     *
     * @return string 
     */
    public function getTicketsLastMonth()
    {
        return $this->ticketsLastMonth;
    }

    /**
     * Set ticketsUntilLastMonth
     *
     * @param string $ticketsUntilLastMonth
     * @return Statistic
     */
    public function setTicketsUntilLastMonth($ticketsUntilLastMonth)
    {
        $this->ticketsUntilLastMonth = $ticketsUntilLastMonth;

        return $this;
    }

    /**
     * Get ticketsUntilLastMonth
     *
     * @return string 
     */
    public function getTicketsUntilLastMonth()
    {
        return $this->ticketsUntilLastMonth;
    }

    /**
     * Set ticketsThisMonth
     *
     * @param string $ticketsThisMonth
     * @return Statistic
     */
    public function setTicketsThisMonth($ticketsThisMonth)
    {
        $this->ticketsThisMonth = $ticketsThisMonth;

        return $this;
    }

    /**
     * Get ticketsThisMonth
     *
     * @return string 
     */
    public function getTicketsThisMonth()
    {
        return $this->ticketsThisMonth;
    }
    /**
     * @var string
     */
    private $ticketsLastMonthPeriod;


    /**
     * Set ticketsLastMonthPeriod
     *
     * @param string $ticketsLastMonthPeriod
     * @return Statistic
     */
    public function setTicketsLastMonthPeriod($ticketsLastMonthPeriod)
    {
        $this->ticketsLastMonthPeriod = $ticketsLastMonthPeriod;

        return $this;
    }

    /**
     * Get ticketsLastMonthPeriod
     *
     * @return string 
     */
    public function getTicketsLastMonthPeriod()
    {
        return $this->ticketsLastMonthPeriod;
    }
}
