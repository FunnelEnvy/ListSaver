<?php
if(!class_exists('List_Saver_Validator'))
include_once('class.validation.php');

if(!class_exists('List_Saver_Database'))
include_once('class.database.php');

class List_Saver_Plugin_Base
{
	
	protected $errors;
	
	protected $success;
	
	private $query;
	
	private $table;
	
	private $unique;

	/**
	 * constructor
	 *
	 * @return POG_Base
	 */
	private function List_Saver_Plugin_Base()
	{
	}

	function getVal($property)
	{
		if(property_exists($this,$property))
		{
			return $this->Unescape($this->Unescape($this->{$property}));
		}
		
	}
	
	function setVal($property,$value)
	{
			if($this->valid($property,$value))
			$this->{$property}=$value;
	}
	
	function valid($property,$value)
	{
		
	
	if(property_exists($this,$property))
		{
			
			$validator = new List_Saver_Validator();
			
			if(isset($this->valiations[$property]))
			{
			foreach($this->valiations[$property] as $type=>$message)
			{
				$validator->add($property,$value,$type,$message);
				
			}
			
			$errors=$validator->validate();
			
			if($errors)
			{
					$this->errors[$property]=$errors[$property];
					return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			return true;
			}
		}
	}
	

	
	
	protected function display_errors()
	{
		
		if(isset($this->errors) and is_array($this->errors))	
		{
			return implode('<br>',$this->errors);
			
		}	
	}

	///////////////////////////
	// Data manipulation
	///////////////////////////

	/**
	* This function will try to encode $text to base64, except when $text is a number. This allows us to Escape all data before they're inserted in the database, regardless of attribute type.
	* @param string $text
	* @return string encoded to base64
	*/
	public function Escape($text)
	{
		
		return mysql_real_escape_string($text);
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $text
	 * @return unknown
	 */
	public function Unescape($text)
	{
		return stripcslashes($text);
	}


	////////////////////////////////
	// Table -> Object Mapping
	////////////////////////////////



	private function PopulateObjectAttributes($fetched_row, $pog_object)
	{
		$att = $this->GetAttributes($pog_object);
 		foreach ($att as $column)
		{
			$pog_object->{$column} = $this->Unescape($fetched_row[strtolower($column)]);
		}
		return $pog_object;
	}

	public function GetAttributes($object, $type='')
	{
		$columns = array();
		foreach ($object->pog_attribute_type as $att => $properties)
		{
			if ($properties['db_attributes'][0] != 'OBJECT')
			{
				if (($type != '' && strtolower($type) == strtolower($properties['db_attributes'][0])) || $type == ''){
					$columns[] = $att;
				}
			}
		}
		return $columns;
	}

	//misc
	public static function IsColumn($value)
	{
		if (strlen($value) > 2)
		{
			if (substr($value, 0, 1) == '`' && substr($value, strlen($value) - 1, 1) == '`')
			{
				return true;
			}
			return false;
		}
		return false;
	}
}
?>
