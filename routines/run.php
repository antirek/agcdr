#!/usr/bin/php
<?php

/*
 * Master routine script.
 *
 * Runs either a single routine (with optional list of parameters) or an entire
 * directory of routines (without parameters).
 *
 * @package	Syml
 * @author	Stuart Benjamin Ford <stuartford@me.com>
 * @copyright	2011
 */

// define paths
define('ROUTINE_PATH',realpath(dirname(__FILE__)));
define('APP_PATH',ROUTINE_PATH."/../application");

// required resources
require_once(APP_PATH."/config.php");
require_once(APP_PATH."/libraries/adodb5/adodb.inc.php");

// register class autoloader function
spl_autoload_register("ClassAutoloader");

// get path to run script from argument
$path = array_shift($argv);

// check parameters
if ($dir = array_shift($argv)) {

	// check directory exists
	if (!is_dir(ROUTINE_PATH."/{$dir}")) die("No such routine directory '{$dir}'.\n");

	// check second parameter for a single routine to run
	if ($script = array_shift($argv)) {

		// check routine exists
		if (!is_file(ROUTINE_PATH."/{$dir}/{$script}.php")) die("No such routine '{$script}' in directory '{$dir}'.\n");
		$routine = array("dir" => $dir,"script" => $script);

		// extract any parameters
		$routine["params"] = (isset($argv[0])) ? $argv : false;

		$routines[] = $routine;

	} else {

		// run all routines in directory (cannot be run with parameters)
		$routines = glob(ROUTINE_PATH."/{$dir}/*.php");
		foreach ($routines as &$routine) {
			$rparts = explode("/",$routine);
			$routine = array(
				"dir"		=> $dir,
				"script"	=> rtrim(array_pop($rparts),".php"),
				"params"	=> false
			);
		}

	}

} else {

	// no parameters passed, cannot continue
	die("Usage: {$path} <directory> [routine]\n");

}

// ask the user which of their CDR databases they wish to use and insert
// the config into constants so they'll be picked up normally (this is the only
// difference in this script from its Syml original)


// run each routine
foreach ($routines as $routine) {
	print "Running routine {$routine['dir']}/{$routine['script']} ...\n";
	require_once(ROUTINE_PATH."/{$routine['dir']}/{$routine['script']}.php");
	$object = new $routine['script']($routine['dir'],$routine['params']);
	$object->go();
}

/**
 * Class autoloader function saves having to manually include each class and model file.
 *
 * @param string $class		- class or model
 *
 * @return void
 */
function ClassAutoloader($class) {
        $directories = array(
                "classes",
                "models",
                "classes/exceptions"
        );
	foreach ($directories as $dir) {
		if (file_exists(APP_PATH."/{$dir}/{$class}.php")) {
			require_once(APP_PATH."/{$dir}/{$class}.php");
		}
	}
}

/**
 * Base routine class.
 */
abstract class BaseRoutine {

	/**
	 * Database object.
	 *
	 * @var object
	 * @access public
	 */
	public $db;

	/**
	 * Container directory.
	 *
	 * @var string
	 * @access public
	 */
	public $dir;

	/**
	 * Logger object;
	 *
	 * @var object
	 * @access public
	 */
	public $logger;

	/**
	 * Optional parameter string.
	 *
	 * @var string
	 * @access public
	 */
	public $params = false;

	/**
	 * Construct.
	 *
	 * @param string $dir		- directory
	 * @param string $params	- (optional) string of parameters
	 *
	 * @access public
	 */
	public function __construct($dir,$params=false) {

		// set directory
		$this->dir = $dir;

		// create database object
		$this->db = DB::Instance();

		// create new logger object
		if (defined('DEBUG_TABLE')) {
			// use database
			$this->logger = new Logger("routines/{$dir}/".get_class($this).".php",false,$this->db,DEBUG_TABLE);
		} else {
			// use file
			$this->logger = new Logger("routines/{$dir}/".get_class($this).".php",DEBUG_FILE,false,false);
		}

		// set parameters and log instance
		if ($params) {
			$this->params = $params;
			$this->logger->info("Routine initialised with parameters '{$this->params}'.");
		} else {
			$this->logger->info("Routine initialised.");
		}

	}

	/**
	 * All child classes must have a main execution function.
	 */
	public function go() { }

	/**
	 * Ask user a question from the command line.
	 *
	 * @param string $text		- question
	 * @param srting $default	- (optional) default answer (default none)
	 * @param boolean $can_be_blank	- (optional) set to true if the answer can be blank (default false)
	 *
	 * @return string		- answer
	 */
	public function question($text,$default=false,$can_be_blank=false) {

		// pose question
		print wordwrap("\n{$text}\n> ",80);
		if ($default) print "[{$default}] ";

		// get answer
		$answer = trim(fgets(STDIN));

		// check answer
		if (strlen($answer) == 0) {
			if ($default) {
				$answer = $default;
			} elseif (!$can_be_blank) {
				print "\nYour answer cannot be blank.\n";
				$answer = self::question($text,$default,$can_be_blank);
			}
		}

		// return answer
		return $answer;

	}

}

?>
