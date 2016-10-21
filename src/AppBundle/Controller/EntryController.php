<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
/**
 * @Route("/entry")
 */
class EntryController extends Controller
{
    /**
     * Force a file to be downloaded
     * 
     * @Route("/{id}/file/{fileName}/download", name="entry_filedownload")
     * @Template()
     */
    public function fileDownloadAction($id, $fileName)
    {
        $em = $this->getDoctrine()->getManager();
        $entry = $em->getRepository('AppBundle:Entry')->find($id);

        if (!$entry) {
            throw $this->createNotFoundException('Unable to find Entry entity.');
        }
        
        $files = $entry->getFilesObject();
        $found = false;
        
        foreach ($files as $file) {
            if ($file->name === $fileName) {
                $found = true;
                break;
            }
        }
        
        if ($found === false) {
            throw $this->createNotFoundException('File not found.');
        } else {
            $dir = $this->get('kernel')->getRootDir() .'/../web/attachments/'. $entry->getTicket()->getId() .'/';
//            header('Content-Type: application/octet-stream');
            header('Content-Type: image/'. $file->extension);
            header("Content-Transfer-Encoding: Binary"); 
            header("Content-disposition: attachment; filename=\"" . $file->originalName . "\""); 
            readfile($dir . $file->name); // do the double-download-dance (dirty but worky)
        }
        
        die;
    }

}
