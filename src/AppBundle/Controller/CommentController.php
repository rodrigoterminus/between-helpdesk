<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Comment;

/**
 * @Route("/comment")
 */
class CommentController extends Controller
{
    /**
     * @Route("/add/{ticketId}", name="comment_add", options={"expose":true})
     */
    public function addAction(Request $request, $ticketId)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        
        $ticket = $em->getRepository('AppBundle:Ticket')->find($ticketId);

        if (!$ticket) {
            throw $this->createNotFoundException('Unable to find Ticket entity.');
        }
        
        $comment = new Comment();
        $comment
            ->setCreatedBy($user)
            ->setCreatedAt(new \DateTime('now'))
            ->setText($request->request->get('comment'))
            ->setTicket($ticket)
            ;
        
        $this->get('app.mailer')
            ->setEvent('comment')
            ->setTicket($ticket)
            ->send();
        
        $em->persist($comment);
        $em->flush();
        
        $response = [
            'text' => $comment->getText(),
            'createdAt' => date_format($comment->getCreatedAt(), 'H:i:s d/m/Y'),
            'createdBy' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
            ],
        ];
        
        return new JsonResponse($response);
    }

    /**
     * @Route("/load/{ticketId}", name="comment_load", options={"expose":true})
     */
    public function loadAction($ticketId)
    {
        return new JsonResponse($response);            
    }

}
