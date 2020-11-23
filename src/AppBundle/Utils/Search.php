<?php

namespace AppBundle\Utils;

class Search {

    public $buttons, $columns, $data, $totalizer, $translate_prefix;

    /**
     * @var string
     */
    private $route;
    
    /**
     * @var array 
     */
    private $result;

    /**
     * @var int
     */
    private $maxResults = 200;

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

    public function onResultClick(string $routeName): array
    {
        return [
            'routeName' => $routeName
        ];
    }

    public function setFormData(Array $data) {
        $this->data = $data;
        return $this;
    }

    public function setTranslatePrefix($translate_prefix) {
        $this->translate_prefix = $translate_prefix;
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

    /**
     * @return int
     */
    public function getMaxResults(): int
    {
        return $this->maxResults;
    }

    /**
     * @param int $maxResults
     * @return Search
     */
    public function setMaxResults(int $maxResults): Search
    {
        $this->maxResults = $maxResults;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param mixed $route
     * @return Search
     */
    public function setRoute($route)
    {
        $this->route = $route;
        return $this;
    }

}