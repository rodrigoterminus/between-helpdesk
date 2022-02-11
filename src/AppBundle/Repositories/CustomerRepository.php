<?php


namespace AppBundle\Repositories;


use Doctrine\ORM\EntityRepository;

class CustomerRepository extends EntityRepository
{
    public function findAll()
    {
        return $this->findBy([
            'deleted' => false
        ]);
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $criteria['deleted'] = $criteria['deleted'] ?? false;
        return parent::findBy($criteria, $orderBy, $limit, $offset); // TODO: Change the autogenerated stub
    }
}