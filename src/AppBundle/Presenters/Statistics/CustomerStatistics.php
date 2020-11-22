<?php


namespace AppBundle\Presenters\Statistics;


use AppBundle\Entity\Customer;
use AppBundle\Entity\Ticket;

class CustomerStatistics
{
    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var int
     */
    private $users;

    /**
     * @var int
     */
    private $projects;

    /**
     * @var array
     */
    private $tickets;

    /**
     * CustomerStatistics constructor.
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
        $this->users = $this->countUsers();
        $this->projects = $this->countProjects();
        $this->tickets = [
            'total' => $this->countTickets(),
            'monthlyAverage' => $this->calculateTicketsPerMonth(),
        ];
    }

    /**
     * @return int
     */
    private function countUsers(): int
    {
        return count($this->customer->getUsers());
    }

    /**
     * @return int
     */
    private function countTickets(): int
    {
        return count($this->customer->getTickets());
    }

    /**
     * @return int
     */
    private function countProjects(): int
    {
        return count($this->customer->getProjects());
    }

    /**
     * @return float
     */
    private function calculateTicketsPerMonth(): float
    {
        $tickets = $this->customer->getTickets();
        $count = count($tickets);

        if ($count === 0) {
            return 0;
        }

        /** @var Ticket $firstTicket */
        $firstTicket = $tickets[0];
        $interval = $firstTicket->getCreatedAt()->diff(new \DateTime());
        $months = ($interval->y * 12 + $interval->m) ?? 1;

        return $count / ($months === 0 ? 1 : $months);
    }

    /**
     * @return int
     */
    public function getUsers(): int
    {
        return $this->users;
    }

    /**
     * @return int
     */
    public function getProjects(): int
    {
        return $this->projects;
    }

    /**
     * @return array
     */
    public function getTickets(): array
    {
        return $this->tickets;
    }

}