<?php
/*
	This SQL query will create the table to store your object.

	CREATE TABLE IF NOT EXISTS list_saver_subscriptions (
             sub_id INT UNSIGNED AUTO_INCREMENT,
			 sub_first_name VARCHAR(100) NOT NULL,
			 sub_last_name VARCHAR(100) NOT NULL,
			 sub_email VARCHAR(100) NOT NULL,
			 sub_ip VARCHAR(30) NOT NULL,
			 sub_status ENUM('a', 'd', 'p') DEFAULT 'p',
			 sub_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			 sub_email_sent ENUM('true','false') DEFAULT 'false',
			 PRIMARY KEY(sub_id),
			 UNIQUE(sub_email)
            ); 

*/

/**
* <b>Subscription</b> class with integrated CRUD methods.
* @author Sandeep Kumar
* @version 1.0
*/
if(!class_exists('List_Saver_Plugin_Base'))
include_once( 'class.base.php' );

class List_Saver_Subscription extends List_Saver_Plugin_Base
{
	public $sub_id = '';

	/**
	 * @var VARCHAR(100)
	 */
	public $sub_first_name;
	
	/**
	 * @var VARCHAR(100)
	 */
	public $sub_last_name;
	/**
	 * @var VARCHAR(255)
	 */
	public $sub_email;
	
	/**
	 * @var VARCHAR(30)
	 */
	public $sub_ip;
	
	/**
	 * @var ENUM('a','d','p')
	 */
	
	public $sub_status;
	
	
	/**
	 * @var TIMESTAMP
	 */
	
	public $sub_date;
	
	/**
	 * @var ENUM('true','false')
	 */
	
	public $sub_email_sent;
	
	public $table;
	
	
	protected $valiations=array(
	
	'sub_email'=>array('req'=>'Email is required.','email'=>'Invalid email format.')
	
	);
	
	function List_Saver_Subscription()
	{
		global $configuration;
		$this->table=TBL_EMAILS;
		$this->unique='sub_id';
		$this->sub_ip=$_SERVER['REMOTE_ADDR'];
		$this->action();
	}
	
	function action()
	{
	}

	/**
	* Load the object from the database
	* @return Void
	*/
	function load($load_id)
	{ 
		$object=$this->Get(array(array("sub_id","=",$load_id)));
		
		if(isset($object))
		{
				$this->fill($object[0]);	
		}
	}
	
	
	function fill($row)
	{
			$this->setVal('sub_id',$row->sub_id);
			$this->setVal('sub_first_name',$row->sub_first_name);
			$this->setVal('sub_last_name',$row->sub_last_name);
			$this->setVal('sub_email',$row->sub_email);
			$this->setVal('sub_ip',$row->sub_ip);
			$this->setVal('sub_status',$row->sub_status);
			$this->setVal('sub_date',$row->sub_date);
			$this->setVal('sub_email_sent',$row->sub_email_sent);
			
			
	}
	
	
	/**
	* Returns a sorted array of objects that match given conditions
	* @param multidimensional array {("field", "comparator", "value"), ("field", "comparator", "value"), ...} 
	* @param string $sortBy 
	* @param boolean $ascending 
	* @param int limit 
	* @return array $ruleList
	*/
	function Get($fcv_array = array(), $sortBy='', $ascending=true, $limit='')
	{
		$connection = List_Saver_Database::Connect();
		$sqlLimit = ($limit != '' ? "LIMIT $limit" : '');
		$this->query = "SELECT * FROM $this->table ";
		$ruleList = Array();
		if (sizeof($fcv_array) > 0)
		{
			$this->query .= " WHERE ";
			for ($i=0, $c=sizeof($fcv_array); $i<$c; $i++)
			{
				if (sizeof($fcv_array[$i]) == 1)
				{
					$this->query .= " ".$fcv_array[$i][0]." ";
					continue;
				}
				else
				{
					if ($i > 0 && sizeof($fcv_array[$i-1]) != 1)
					{
						$this->query .= " AND ";
					}
					if (isset($this->pog_attribute_type[$fcv_array[$i][0]]['db_attributes']) && $this->pog_attribute_type[$fcv_array[$i][0]]['db_attributes'][0] != 'NUMERIC' && $this->pog_attribute_type[$fcv_array[$i][0]]['db_attributes'][0] != 'SET')
					{
						if ($GLOBALS['configuration']['db_encoding'] == 1)
						{
							$value = List_Saver_Plugin_Base::IsColumn($fcv_array[$i][2]) ? "BASE64_DECODE(".$fcv_array[$i][2].")" : "'".$fcv_array[$i][2]."'";
							$this->query .= "BASE64_DECODE(`".$fcv_array[$i][0]."`) ".$fcv_array[$i][1]." ".$value;
						}
						else
						{
							$value =  List_Saver_Plugin_Base::IsColumn($fcv_array[$i][2]) ? $fcv_array[$i][2] : "'".$this->Escape($fcv_array[$i][2])."'";
							$this->query .= "`".$fcv_array[$i][0]."` ".$fcv_array[$i][1]." ".$value;
						}
					}
					else
					{
						$value = List_Saver_Plugin_Base::IsColumn($fcv_array[$i][2]) ? $fcv_array[$i][2] : "'".$fcv_array[$i][2]."'";
						$this->query .= "`".$fcv_array[$i][0]."` ".$fcv_array[$i][1]." ".$value;
					}
				}
			}
		}
		if ($sortBy != '')
		{
			if (isset($this->pog_attribute_type[$sortBy]['db_attributes']) && $this->pog_attribute_type[$sortBy]['db_attributes'][0] != 'NUMERIC' && $this->pog_attribute_type[$sortBy]['db_attributes'][0] != 'SET')
			{
				if ($GLOBALS['configuration']['db_encoding'] == 1)
				{
					$sortBy = "BASE64_DECODE($sortBy) ";
				}
				else
				{
					$sortBy = "$sortBy ";
				}
			}
			else
			{
				$sortBy = "$sortBy ";
			}
		}
		else
		{
			$sortBy = $this->unique;
		}
		$this->query .= " ORDER BY ".$sortBy." ".($ascending ? "ASC" : "DESC")." $sqlLimit";
		$thisObjectName = get_class($this);
		$cursors = List_Saver_Database::Reader($this->query, $connection);
		
		foreach( $cursors as $row)
		{
			
			$obj = new $thisObjectName();
			$obj->fill($row);
			$objects[] = $obj;
		}
		
		return $objects;
	}
	
	

	
	
	/**
	* Saves the object to the database
	* @return integer $rule_id
	*/
	function Save()
	{
		$connection = List_Saver_Database::Connect();
		$rows = 0;
		if ( $this->rule_id != '' ){
			$this->query = $connection->prepare("SELECT $this->unique FROM $this->table WHERE $this->unique='%d' LIMIT 1",$this->rule_id);
			$rows = List_Saver_Database::Query($this->query, $connection);
		}
		
			$data['sub_first_name'] = $this->Escape($this->sub_first_name);
			$data['sub_last_name'] = $this->Escape($this->sub_last_name);
			$data['sub_email'] = $this->Escape($this->sub_email);
			$data['sub_ip']  = $this->Escape($this->sub_ip);
			$data['sub_email_sent']  = $this->Escape($this->sub_email_sent);
		
		if ($rows > 0 )
		{
		$where['sub_id']=$this->Escape($this->sub_id);
		}
		else
		{
			$where='';
		}
	
		$insertId = List_Saver_Database::InsertOrUpdate($this->table,$data,$where);
		
		if ($this->sub_id == "")
		{
			$this->sub_id = $insertId;
		}
		return $this->sub_id;
	}
	
	
	/**
	* Deletes the object from the database
	* @return boolean
	*/
	function Delete()
	{
		$connection = List_Saver_Database::Connect();
		$this->query = $connection->prepare("DELETE FROM $this->table WHERE $this->unique='%d'",$this->sub_id);
		return List_Saver_Database::NonQuery($this->query, $connection);
	}
	
	/**
	* Deletes the object from the database
	* @return boolean
	*/
	function list_saver_mailchimp_subscriber_delete()
	{
	
		$connection = List_Saver_Database::Connect();
		$this->query = $connection->prepare("DELETE FROM $this->table WHERE sub_status='%s' or sub_email_sent='%s' ",'a','true');
		return List_Saver_Database::NonQuery($this->query, $connection);
	
	}
	
	 /**
	 * View for Manage Subscriptions
	 * @return HTML Structure to manage Subscriptions 
	 */
	 
	 public function view_subscriptions($status)
	 {  
 
        if( isset($_POST) && $_POST['pending_subscriber'] == 'send' ){
         list_saver_mailchimp_subscriber_event(false);
         list_saver_manage_show_message('Reminder email sent successfully.');
        }

		if( isset($_POST) && $_POST['delete_subscriber'] == 'delete' ){
         $this->list_saver_mailchimp_subscriber_delete();
         list_saver_manage_show_message('Active & Email Sent subscribers deleted successfully.');
        }
		 $all_subscriptions=$this->Get(array(array('sub_status','=',$status)));
		 ob_start();
		 include( list_saver_VIEWS_PATH . '/view-manage-subscriptions.php');
		 $output=ob_get_contents();
		 ob_clean();
		 
		 return $output;
	 }	
}
?>
