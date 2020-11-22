<?php


namespace AppBundle\Services;


use AppBundle\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

class ProjectService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function remove(Project $project, bool $flush = true)
    {
        if ($project->getCustomer() || count($project->getTickets())) {
            $project->setDeleted(true);
        } else {
            $this->em->remove($project);
        }

        if ($flush) {
            $this->em->flush();
        }
    }
}