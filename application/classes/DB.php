<?php

/**
 * Simple database access class.
 * 
 * Requires ADOdb.
 * 
 * @package		SBF-Classlib
 * @author		Stuart Benjamin Ford <stuartford@me.com>
 * @copyright		15/01/2011
 */

/**
 * Simple database access class, requires AdoDB.
 */
class DB {

	/**
	 * Database resource.
	 * 
	 * @var resource
	 * @access private
	 */
	private $db;

	/**
	 * Database type.
	 * 
	 * @var string
	 * @access public
	 */
	public $db_type = DB_TYPE;
	
	/**
	 * Database server hostname.
	 * 
	 * @var string
	 * @access public
	 */
	public $db_host = DB_HOST;
	
	/**
	 * Database username.
	 * 
	 * @var string
	 * @access public
	 */
	public $db_user = DB_USER;
	
	/**
	 * Database password.
	 * 
	 * @var string
	 * @access public
	 */
	public $db_pass = DB_PASS;
	
	/**
	 * Database engine type.
	 * 
	 * @var string
	 * @access public
	 */
	public $db_name = DB_NAME;
	
	/**
	 * Construct by establishing connection to database.
	 * 
	 * @access public
	 */
	public function __construct() {
		$this->db =NewADOConnection($this->db_type);
		$this->db->SetFetchMode(ADODB_FETCH_ASSOC);
		$this->db->PConnect($this->db_host,$this->db_user,$this->db_pass,$this->db_name);
	}

	/**
	 * Create database instance.
	 * 
	 * @return resource		- database instance resource
	 * @access public
	 */
	public static function &Instance() {
		static $db;
		if ($db === null) $db = new DB();
		return $db;
	}

	/**
	 * Enable or disable debugging mode.
	 * 
	 * @param boolean $debug
	 * 
	 * @return void
	 * @access public
	 */
	public static function debug($debug=true) {
		$db = self::Instance();
		$db->debug = $debug;
	}

	/**
	 * Call.
	 * 
	 * @param string $func
	 * @param array $args
	 * 
	 * @access public
	 */
	public function __call($func,$args) {
		if (is_callable(array($this->db,$func))) {
			return call_user_func_array(array($this->db,$func),$args);
		}
	}

	/**
	 * Generic setter.
	 * 
	 * @param mixed $key
	 * @param mixed $var
	 * 
	 * @return void
	 * @access public
	 */
	public function __set($key,$var) {
		$this->db->$key = $var;
	}

	/**
	 * Generic getter.
	 * 
	 * @param mixed $key
	 * 
	 * @return mixed		- value of key
	 * @access public
	 */
	public function __get($key) {
		return $this->db->$key;
	}

	/**
	 * Retrieve possible values for an enumnerated type.
	 * 
	 * Believe it or not, ADOdb does not have a method for this. Only certain
	 * database types are supported by this function (MySQL and PostgreSQL).
	 * 
	 * @param string $table		- table name
	 * @param string $field		- field name
	 * 
	 * @return array		- possible values, or false if DB type not supported
	 * @access public
	 */
	public function enum_values($table,$field) {

		switch ($this->db_type) {
			
			case "mysql":
			case "mysqli":
				
				$info = $this->GetRow("SHOW COLUMNS FROM `{$table}` LIKE '{$field}';");
				preg_match_all("/'(.*?)'/",$info["Type"],$values);
				$values = $values[1];

				break;
				
			case "postgres":
				
				$enumtype = $this->GetOne("SELECT udt_name FROM information_schema.columns WHERE table_name = '{$table}' AND column_name = '{$field}';");
				$values = $this->GetCol("SELECT e.enumlabel FROM pg_enum e JOIN pg_type t ON e.enumtypid = t.oid WHERE t.typname = '{$enumtype}';");
				
				break;
				
			default:
				
				// not all ADOdb drivers are supported
				return false;
				break;

		}

		return $values;
		
	}
	
}

?>