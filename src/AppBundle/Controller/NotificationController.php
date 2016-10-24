<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/notification")
 */
class NotificationController extends Controller
{
    /**
     * @Route("/load", name="notification_load", options={"expose": true})
     */
    public function loadAction()
    {
        $notifications = $this->get('app.notifier')->get();
        
        return new JsonResponse($notifications);
    }

}
