<?php


namespace AppBundle\Services;


use AppBundle\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

class CategoryService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function remove(Category $category, bool $flush = true)
    {
        if (count($category->getTickets())) {
            $category->setDeleted(true);
        } else {
            $this->em->remove($category);
        }

        if ($flush) {
            $this->em->flush();
        }
    }
}