<?php

namespace AppBundle\Controller;

use AppBundle\Utils\Notifier;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/notification")
 */
class NotificationController extends Controller
{
    /**
     * @var Notifier
     */
    private $notifier;

    public function __construct(Notifier $notifier)
    {
        $this->notifier = $notifier;
    }

    /**
     * @Route("/load", name="notification_load", options={"expose": true})
     */
    public function loadAction()
    {
        $notifications = $this->notifier->get();
        
        return new JsonResponse($notifications);
    }

}
