<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/dashboard")
 */
class DashboardController extends Controller
{
    /**
     * @Route("/admin", name="dashboard_admin")
     */
    public function adminAction()
    {
        return $this->redirectToRoute('ticket');

        $user = $this->getUser();

        if ($user->isAdmin()) {
            $statisticRepository = $this->getDoctrine()->getRepository('AppBundle:Statistic');
            $ticketRepository = $this->getDoctrine()->getRepository('AppBundle:Ticket');
            $ratingRepository = $this->getDoctrine()->getRepository('AppBundle:Rating');

            $statistic = $statisticRepository->get();

            // Running tickets
            $running = $ticketRepository->findBy([
                'attendant' => $user,
                'status' => 'running',
            ]);

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

            return $this->render('AppBundle:Core:dashboard-admin.html.twig', [
                'statistic' => $statistic,
                'running' => $running,
                'rating' => $rating,
                'title' => 'Dashboard',
            ]);
        } else {
            return $this->redirectToRoute('index_customer');
        }
    }

    /**
     * @Route("/customer", name="dashboard_customer")
     * @Template()
     */
    public function customerAction()
    {
        return $this->redirectToRoute('ticket');

        $user = $this->getUser();
        $ticketRepository = $this->getDoctrine()->getRepository('AppBundle:Ticket');

        // Get unrated tickets
        $qb = $ticketRepository->createQueryBuilder('ticket')
            ->select([
                'ticket.id',
                'ticket.number',
                'ticket.subject',
                'ticket.finishedAt',
                'rating.rate',
                'attendant.name',
            ])
            ->leftJoin('AppBundle:Rating', 'rating', 'WITH', 'rating.ticket = ticket.id')
            ->leftJoin('AppBundle:User', 'attendant', 'WITH', 'attendant.id = ticket.attendant')
            ->where('ticket.customer = :customer')
            ->andWhere('ticket.status = :status')
            ->andWhere('rating.ticket IS NULL')
            ->setParameters([
                'customer' => $user->getCustomer()->getId(),
                'status' => 'finished'
            ])
            ;
        $tickets = $qb->getQuery()->getResult();

        if (count($tickets) === 0) {
            return $this->redirectToRoute('ticket');
        } else {
            return $this->render('AppBundle:Core:dashboard-customer.html.twig', [
                'tickets' => $tickets,
                'title' => 'Dashboard',
            ]);
        }
    }
}
