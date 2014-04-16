<?php
/**
* <b>Database Connection</b> class.
* @author Sandeep Kumar
* @version 1.0
*/
 Class List_Saver_Database
{
	private static $connection;

	private function List_Saver_Database()
	{
		global $wpdb;
		$this->connection=$wpdb;
	}

	public static function Connect()
	{
		global $wpdb;
		return $wpdb;
	}

	public static function Reader($query, $connection)
	{
		$cursor = $connection->get_results($query);
		return $cursor;
	}

	public static function Read($cursor)
	{
		return $cursor[0];
	}

	public static function NonQuery($query, $connection)
	{
		
		$result = $connection->query($query);
		if ($result == 0 or $result=='FALSE')
		{
			return false;
		}
		return $result;

	}

	public static function Query($query, $connection)
	{
		$result = $connection->query($query);
		return $result;
	}

	public static function InsertOrUpdate($table,$data,$where='')
	{
		
		global $wpdb;
		
		$wpdb->show_errors();
		
		if(!is_array($where))
		$result =$wpdb->insert( $table, $data);
		else
		$result =$wpdb->update( $table, $data, $where);

		return intval($result);
	}
}
?>
