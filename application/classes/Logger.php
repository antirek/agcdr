<?php

/**
 * General logging and debugging class.
 *
 * @package	Syml
 * @author	Stuart Benjamin Ford <stuartford@me.com>
 * @copyright	16/02/2011
 */

/**
 * Logger.
 */
class Logger {

	/**
	 * Log file handle object.
	 *
	 * @var string
	 * @access public
	 */
	public $filehandle = false;

	/**
	 * Database object.
	 *
	 * @var object
	 * @access public
	 */
	public $dbhandle = false;

	/**
	 * Database table name.
	 *
	 * Database table must have the following fields as a minimum. Datatypes
	 * recommended are (in order) for MySQL and PostgreSQL. You'll probably
	 * want an ID field too. Include additional fields as required.
	 *
	 * datetime (DATETIME)
	 * script (VARCHAR or CHARACTER VARYING)
	 * user (VARCHAR or CHARACTER VARYING)
	 * severity (VARCHAR or CHARACTER VARYING)
	 * message (TEXT or CHARACTER VARYING)
	 *
	 * @var string
	 * @access public
	 */
	public $dbtable = false;

	/**
	 * Script name.
	 *
	 * @var string
	 * @access public
	 */
	public $script;

	/**
	 * Current user.
	 *
	 * @var string
	 * @access private
	 */
	private $user;

	/**
	 * Construct.
	 *
	 * Pass neither $filepath or $dbo to log to stdout. Passing either or
	 * both will disable stdout logging and will log to the respective
	 * resources.
	 *
	 * @param string $script	- script name
	 * @param string $filepath	- (optional) log to file path instead of stdout
	 * @param object $dbo		- (optional) ADOdb database object, if passed you must also pass $dbtable
	 * @param string $dbtable	- (optional) database table name, required if $dbo is passed
	 *
	 * @access public
	 */
	public function __construct($script,$filepath=false,$dbo=false,$dbtable=false) {

		// set user properties
		$this->script = $script;
		$this->user = chop(`whoami`);

		// create filehandle if filepath passed
		if ($filepath) {
			if ($fh = fopen($filepath,"a")) {
				$this->filehandle = $fh;
			} else {
				die("Unable to open {$filepath} for writing.");
			}
		}

		// set database properties if passed
		if ($dbo && $dbtable) {
			$this->dbhandle = $dbo;
			$this->dbtable = $dbtable;
		}

	}

	/**
	 * Debug-level message.
	 *
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access public
	 */
	public function debug($message) {
		$this->writelog("debug",$message);
	}

	/**
	 * Info-level message.
	 *
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access public
	 */
	public function info($message) {
		$this->writelog("info",$message);
	}

	/**
	 * Notice-level message.
	 *
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access public
	 */
	public function notice($message) {
		$this->writelog("notice",$message);
	}

	/**
	 * Warning-level message.
	 *
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access public
	 */
	public function warning($message) {
		$this->writelog("warning",$message);
	}

	/**
	 * Error-level message.
	 *
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access public
	 */
	public function error($message) {
		$this->writelog("error",$message);
	}

	/**
	 * Critical-level message.
	 *
	 * This message level forces termination to cease.
	 *
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access public
	 */
	public function critical($message) {
		$this->writelog("critical",$message);
		$this->notice("Terminating execution on critical error.");
		exit;
	}

	/**
	 * Alert-level message.
	 *
	 * This message level forces termination to cease.
	 *
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access public
	 */
	public function alert($message) {
		$this->writelog("alert",$message);
		$this->notice("Terminating execution on alert error.");
		exit;
	}

	/**
	 * Emergency-level message.
	 *
	 * This message level forces termination to cease.
	 *
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access public
	 */
	public function emergency($message) {
		$this->writelog("emergency",$message);
		$this->notice("Terminating execution on emergency error.");
		exit;
	}

	/**
	 * Generic wrapper for shortcut log functions.
	 *
	 * If the passed severity name does not exist then the message is logged
	 * with the "notice" severity.
	 *
	 * @param string $severity	- severity
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access public
	 */
	public function log($severity,$message) {

		if (method_exists($severity)) {
			$this->$severity($message);
		} else {
			$this->notice($message);
		}

	}

	/**
	 * Output a log line.
	 *
	 * @param string $severity	- severity
	 * @param string $message	- message (without carriage return)
	 *
	 * @return void
	 * @access private
	 */
	private function writelog($severity,$message) {

		// log message via file handle if present
		if ($this->filehandle) {
			$line = date("Y-m-d H:i:s")." {$this->script} {$this->user} ".strtoupper($severity).": {$message}\n";
			fwrite($this->filehandle,$line);
			$written = true;
		}

		// log message to database if handle present
		if ($this->dbhandle) {
			$record = array(
				"datetime"	=> date("Y-m-d H:i:s"),
				"script"	=> $this->script,
				"user"		=> $this->user,
				"severity"	=> strtoupper($severity),
				"message"	=> $message
			);
			$this->dbhandle->AutoExecute($this->dbtable,$record,"INSERT");
			$written = true;
		}

		// if we've not written anything to anywhere yet then log to stdout
		if (!isset($written)) {
			print strtoupper($severity).": {$message}\n";
		}

	}

}

?>
