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
//        $data = json_decode('{"title":"Chamados","criteria":[{"label":"Cliente","value":"Réplica"},{"label":"Data inicial","value":"01/07/2015"},{"label":"Data final","value":"21/10/2016"}],"columns":["Número","Cliente","Assunto","Status","Prioridade","Atendente","Criado em"],"results":[["201600000010","Réplica","Take test","Em atendimento","Baixa","Bruno Ribeiro","20/10/2016 09:25:32"],["201600000009","Réplica","Teste de log de ações","Em atendimento","Baixa","Weider Duarte","19/10/2016 20:21:37"],["201600000008","Réplica","private function moveFiles(&$em, $entry, $files)","Em atendimento","Baixa","Admin do Sistema","18/10/2016 23:13:38"],["201600000004","Réplica","Teste de emails","Em atendimento","Baixa","Weider Duarte","17/10/2016 05:20:20"],["201600000003","Réplica","adfadfad","Em atendimento","Baixa","Bruno Ribeiro","08/08/2016 15:59:21"],["201600000002","Réplica","adfadf","Aguardando atendimento","Alta","-","08/08/2016 15:58:48"],["201600000001","Réplica","3e32d23","Aguardando atendimento","Baixa","-","08/08/2016 04:53:00"],["201500000003","Réplica","adfadf","Aguardando atendimento","Baixa","-","08/08/2016 04:52:21"],["201600000002","Réplica","2222222","Finalizado","Baixa","Bruno Ribeiro","08/08/2016 04:51:55"],["201600000001","Réplica","dadfadfadf","Aguardando atendimento","Média","-","08/08/2016 04:50:51"],["201600000001","Réplica","654345","Aguardando atendimento","Alta","-","08/08/2016 04:49:34"],["201600000001","Réplica","adfadfad","Aguardando atendimento","Baixa","-","08/08/2016 04:21:35"],["201600000001","Réplica","adfadf","Aguardando atendimento","Baixa","-","08/08/2016 04:18:17"],["201600000000","Réplica","adfadf","Aguardando atendimento","Média","-","08/08/2016 04:17:11"],["201600000001","Réplica","adfadf","Aguardando atendimento","Média","-","08/08/2016 04:10:00"],["201600000001","Réplica","adfadf","Aguardando atendimento","Média","-","08/08/2016 04:03:00"],["201600000001","Réplica","adf","Aguardando atendimento","Média","-","08/08/2016 02:15:31"],["201600000001","Réplica","Teste trigger 3","Aguardando atendimento","Baixa","-","08/08/2016 02:12:25"],["201600000001","Réplica","Teste trigger 2","Aguardando atendimento","Média","-","08/08/2016 01:59:47"],["201600000001","Réplica","Teste trigger","Em atendimento","Média","Weider Duarte","08/08/2016 01:59:16"],["201500000009","Réplica","Teste trigger 2","Aguardando atendimento","Média","-","08/08/2016 01:52:55"],["201500000010","Réplica","Teste trigger","Aguardando atendimento","Baixa","-","08/08/2016 01:49:40"],["201500000003","Réplica","Assunto do chamado","Aguardando atendimento","Média","-","13/08/2015 03:49:23"],["201500000002","Réplica","Chamado de pique","Finalizado","Média","Admin do Sistema","13/08/2015 03:39:20"]], "url":"http://between.local/app_dev.php/ticket/?form%5Bnumber%5D=&form%5Bsubject%5D=&form%5Bcustomer%5D=1&form%5Battendant%5D=&form%5Bstatus%5D=&form%5Bproject%5D=&form%5Bdate_initial%5D=2015-07-01&form%5Bdate_final%5D=2016-10-21"}');
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
