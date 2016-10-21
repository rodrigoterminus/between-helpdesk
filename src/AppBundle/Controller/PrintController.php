<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/print")
 */
class PrintController extends Controller
{
    /**
     * @Route("/list", name="print_list", options={"expose"=true})
     * @Template()
     */
    public function listAction()
    {
        return array();    
    }

    /**
     * @Route("/form", name="print_form", options={"expose"=true})
     * @Template()
     */
    public function formAction()
    {
        $company = $this->getDoctrine()
            ->getRepository('InfinityBaseBundle:Company')
            ->find(1);

        return array(
            'company' => $company
        );    
    }

}
