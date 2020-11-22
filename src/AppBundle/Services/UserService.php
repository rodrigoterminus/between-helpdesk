<?php


namespace AppBundle\Services;


use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function remove(User $user, bool $flush = true)
    {
        $user->setDeleted(true);

        if ($flush) {
            $this->em->flush();
        }
    }
}