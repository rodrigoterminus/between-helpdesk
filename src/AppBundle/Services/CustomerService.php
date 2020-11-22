<?php


namespace AppBundle\Services;


use AppBundle\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;

class CustomerService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ProjectService
     */
    private $projectService;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(
        EntityManagerInterface $em,
        ProjectService $projectService,
        UserService $userService
    )
    {
        $this->em = $em;
        $this->projectService = $projectService;
        $this->userService = $userService;
    }

    public function delete(Customer $customer, bool $flush = true)
    {
        if (count($customer->getTickets()) > 0) {
            $customer->setDeleted(true);
        } else {
            $this->em->remove($customer);
        }

        foreach ($customer->getProjects() as $project) {
            $this->projectService->remove($project, false);
        }

        foreach ($customer->getUsers() as $user) {
            $this->userService->remove($user, false);
        }

        if ($flush) {
            $this->em->flush();
        }
    }
}