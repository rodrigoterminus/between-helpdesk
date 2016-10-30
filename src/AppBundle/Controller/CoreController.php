<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\Query\ResultSetMapping;

class CoreController extends Controller
{
    /**
     * @Route("/", name="index")
     * @Template()
     */
    public function indexAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        
        if ($user->isAdmin()) {
            $statisticRepository = $this->getDoctrine()->getRepository('AppBundle:Statistic');
            $ticketRepository = $this->getDoctrine()->getRepository('AppBundle:Ticket');
            $ratingRepository = $this->getDoctrine()->getRepository('AppBundle:Rating');
            
            // Running tickets
            $running = [];            
            $statistic = $statisticRepository->get();
            
            // Rating General
            $rating = [];
            $qb = $ratingRepository->createQueryBuilder('rating')
                ->addSelect('AVG(rating.rate)');
            $rating['general']['rate'] = $qb->getQuery()->getOneOrNullResult()[1];
            
            $qb = $ratingRepository->createQueryBuilder('rating')
                ->addSelect('COUNT(rating.id)')
                ->where('rating.solved = 0');
            $rating['general']['solved'][] = $qb->getQuery()->getOneOrNullResult()[1];
            
            $qb = $ratingRepository->createQueryBuilder('rating')
                ->addSelect('COUNT(rating.id)')
                ->where('rating.solved = 1');
            $rating['general']['solved'][] = $qb->getQuery()->getOneOrNullResult()[1];
            
            // Rating user
            $qb = $ratingRepository->createQueryBuilder('rating')
                ->select([
                    'AVG(rating.rate)',
                ])
                ->join('AppBundle:Ticket', 'ticket', 'WITH', 'ticket.id = rating.ticket')
                ->where('ticket.attendant = '. $user->getId());
            $rating['user']['rate'] = $qb->getQuery()->getOneOrNullResult()[1];
            
            $qb = $ratingRepository->createQueryBuilder('rating')
                ->addSelect('COUNT(rating.id)')
                ->join('AppBundle:Ticket', 'ticket','WITH', 'ticket.id = rating.ticket')
                ->where('rating.solved = 0')
                ->andWhere('ticket.attendant = '. $user->getId());
            $rating['user']['solved'][] = $qb->getQuery()->getOneOrNullResult()[1];
            
            $qb = $ratingRepository->createQueryBuilder('rating')
                ->addSelect('COUNT(rating.id)')
                ->join('AppBundle:Ticket', 'ticket','WITH', 'ticket.id = rating.ticket')
                ->where('rating.solved = 1')
                ->andWhere('ticket.attendant = '. $user->getId());
            $rating['user']['solved'][] = $qb->getQuery()->getOneOrNullResult()[1];
            
            return $this->render('AppBundle:Core:index.html.twig', [
                'statistic' => $statistic,
                'running' => $running,
                'rating' => $rating,
//                'tickets' => $tickets,
            ]);
        } else {
            return $this->redirectToRoute('ticket');
        }    
    }
    
    /**
     * @Route("/customer", name="index_customer")
     * @Template()
     */
    public function indexCustomerAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $ticketRepository = $this->getDoctrine()->getRepository('AppBundle:Ticket');
        
        return $this->render('AppBundle:Core:index-customer.html.twig', [
        ]);
    }
}
