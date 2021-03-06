<?php

namespace AppBundle\Utils;

class Between {
    public function formatDateDiff($start, $end=null) { 
        if(!($start instanceof \DateTime)) { 
            $start = new \DateTime($start); 
        } 

        if($end === null) { 
            $end = new \DateTime(); 
        } 

        if(!($end instanceof \DateTime)) { 
            $end = new \DateTime($start); 
        } 

        $interval = $end->diff($start); 
        $doPlural = function($nb,$str){return $nb>1?$str.'s':$str;}; // adds plurals 

        $format = array(); 
        if($interval->y !== 0) { 
            $format[] = "%y ".$doPlural($interval->y, "ano"); 
        } 
        if($interval->m !== 0) { 
            $format[] = "%m ".$doPlural($interval->m, "mês"); 
        } 
        if($interval->d !== 0) { 
            $format[] = "%d ".$doPlural($interval->d, "dia"); 
        } 
        if($interval->h !== 0) { 
            $format[] = "%h ".$doPlural($interval->h, "hora"); 
        } 
        if($interval->i !== 0) { 
            $format[] = "%i ".$doPlural($interval->i, "minuto"); 
        } 
        if($interval->s !== 0) { 
            if(!count($format)) { 
                return "menos de um minuto"; 
            } else { 
                $format[] = "%s ".$doPlural($interval->s, "segundo"); 
            } 
        } 

        // We use the two biggest parts 
        if (count($format) > 1) { 
            $format = array_shift($format)." e ".array_shift($format); 
        } else { 
            $format = array_pop($format); 
        } 

        // Prepend 'since ' or whatever you like 
        return $interval->format($format); 
    } 
}