<?php

namespace AppBundle\Utils;

class Search {

    public $buttons, $columns, $data, $totalizer, $translate_prefix;
    
    /**
     * @var array 
     */
    private $result;

    public function __construct() {
        $this->buttons = array();
        $this->columns = array();
        $this->form_data = array();

        return $this;
    }

    public function addButton(Array $properties) {
        array_push($this->buttons, $properties);

        return $this;
    }

    public function addColumn(Array $properties) {
        array_push($this->columns, $properties);

        return $this;
    }

    public function setFormData(Array $data) {
        $this->data = $data;
        return $this;
    }

    public function setTranslatePrefix($translate_prefix) {
        $this->translate_prefix = $translate_prefix;
        return $this;
    }

    public function totalizer($result) {
        $totals = array();

        // Create array and prepare the keys to be added up
        foreach ($this->columns as $column) {
            if (array_key_exists('add_up', $column) && $column['add_up'] == true) {
                $totals[$column['name']] = 0;
            }
        }

        foreach ($result as $row) {
            foreach ($row as $column => $value) {
                if (array_key_exists($column, $totals)) {
                    $totals[$column] += $value;
                }
            }
        }

        $this->totalizer = $totals;

        return $this;
    }
    
    /**
     * 
     * @return string
     */
    public function toJSON() {
        $data = [];
        
        if ($this->result !== null) {
            foreach ($this->result as $row) {
                $entry = [];

                foreach ($row as $column => $value) {
                    if ($value instanceof \Date) {
                        $value = $value->getTimestamp();
                    } elseif ($value instanceof \DateTime) {
                        $value = $value->getTimestamp();
                    } 
                    
                    $entry[$column] = $value;
                }

                array_push($data, $entry);
            }
        }        

        return json_encode($data);
    }
    
    /**
     * 
     * @param array $result
     * @return \AppBundle\Utils\Search
     */
    public function setResult($result)
    {
        $this->result = $result;
        
        return $this;
    }
}