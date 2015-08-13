<?php
namespace AppBundle\Utils;

class Search
{
	public $buttons, $columns, $data, $totalizer, $translate_prefix;

	public function __construct()
	{
		$this->buttons = array();
		$this->columns = array();
		$this->form_data = array();

		return $this;
	}

	public function addButton(Array $properties)
	{
		array_push($this->buttons, $properties);

		return $this;
	}

	public function addColumn(Array $properties)
	{
		array_push($this->columns, $properties);

		return $this;
	}

	public function setFormData(Array $data)
	{
		$this->data = $data;
		return $this;	
	}

	public function setTranslatePrefix($translate_prefix)
	{
		$this->translate_prefix = $translate_prefix;
		return $this;	
	}

    public function totalizer($result){
    	$totals = array();

    	// Create array and prepare the keys to be added up
    	foreach($this->columns as $column)
    	{
    		if(array_key_exists('add_up', $column) && $column['add_up'] == true)
    		{
    			$totals[$column['name']] = 0;
    		}
    	}

    	foreach ($result as $row) 
    	{
    		foreach ($row as $column => $value) 
    		{
    			if (array_key_exists($column, $totals)) {
    				$totals[$column] += $value;
    			}
    		}
    	}

    	$this->totalizer = $totals;

    	return $this;
    }
}
?>