<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * @Route("/export")
 */
class ExportController extends Controller
{
    /**
     * @Route("/{format}", name="export", options={"expose"=true})
     * @Method("POST")
     */
    public function indexAction(Request $request, $format)
    {        
        $data = json_decode($request->request->get('data'));
        $filename = 'Between - ' . $data->title . '.'. $format;
        
        $locator = new FileLocator(array($this->get('kernel')->getRootDir() .'/../web/assets/css/'));
        $css = file_get_contents($locator->locate('export.css', null, true));
        
        $locator = new FileLocator(array($this->get('kernel')->getRootDir() .'/../web/assets/images/'));
        $logoFile = $locator->locate('logo-bw.png', null, true);
        $logo = 'data:' . mime_content_type($logoFile) . ';base64,' . base64_encode(file_get_contents($logoFile));
        
        $render = $this->render('AppBundle:Export:export.html.twig', [
            'data' => $data,
            'css' => $css,
            'logo' => $logo
        ]);
                        
        switch ($format) {
            case 'html':                
                return new Response(
                    $render->getContent(),
                    200,
                    array(
                        'Content-Type' => 'text/html',
                        'Content-Disposition' => 'attachment; filename="'. $filename .'"'
                    )
                );
                break;
            
            case 'pdf':                
                return new Response(
                    $this->get('knp_snappy.pdf')->getOutputFromHtml(utf8_decode($render->getContent())),
                    200,
                    array(
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="'. $filename .'"'
                    )
                );
                break;
            
            case 'csv':
                header('Content-Transfer-Encoding: Binary'); 
                header('Content-disposition: attachment; filename="'. $filename .'"'); 
                header('Content-Type: text/csv');
                
                $handle = fopen('php://output', 'w+');
                
                // Add the header of the CSV file
                $header = [];

                foreach ($data->columns as $column) {
                    array_push($header, utf8_decode($column));
                }

                fputcsv($handle, $header,';');
                
                // Add results
                foreach ($data->results as $result) {
                    $line = [];
                    
                    foreach ($result as $value) {
                        array_push($line, utf8_decode($value));
                    }
                    
                    fputcsv($handle, $line,';');
                }
                
                fclose($handle);
                break;
            default:
                return $render; 
        }
        
        die;
    }
}
