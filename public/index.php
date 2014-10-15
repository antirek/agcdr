<?php

/**
 * Main application access and setup script.
 * 
 * All user-configurable configuration can be found in application/config.php.
 * You should not under normal circumstances need to alter this file.
 * 
 * @package	AGCDR
 * @author	SBF
 * @copyright	2011
 */

// development mode flag
define('DEVMODE',true);

// set error reporting
if (DEVMODE == true) {
	error_reporting(E_ALL ^ E_STRICT);
} else {
	error_reporting(E_ERROR);
}
	
// set locale
setlocale(LC_ALL,'en_GB');

// start session
session_start();

// define site paths
define('PUB_PATH',realpath(dirname(__FILE__)));
define('APP_PATH',PUB_PATH."/../application");
define('DATA_PATH',PUB_PATH."/../data");
define('CHART_CACHE',PUB_PATH."/images/charts");

// include configuration script
require_once(APP_PATH."/config.php");

// required resources
require_once(APP_PATH."/libraries/adodb5/adodb.inc.php");
require_once(APP_PATH."/libraries/Smarty-3.0.7/libs/Smarty.class.php");

// set application URL
if (isset($_SERVER["SERVER_NAME"])) {
	if (BETA) {
		define('APP_URL',"http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}/");
	} else {
		define('APP_URL',"http://{$_SERVER['SERVER_NAME']}/");
	}
}

// check that at least one server is defined in the configuration
// this is a basic installation step, so be blunt if it's missing
if (!isset($_SESSION["servers"]) || count($_SESSION["servers"]) == 0) {
	$stopmsg = "No CDR database servers are configured in application/config.php.";
	print "<h3>{$stopmsg}</h3>";
	throw new Exception($stopmsg);
}

// determine server choice, either from session, switcher menu or default
if (isset($_POST["switchto"])) $_SESSION["server"] = $_POST["switchto"];
if (!isset($_SESSION["server"])) {
	$server = array_shift(array_keys($_SESSION["servers"]));
	$_SESSION["server"] = $server;
} else {
	$server = $_SESSION["server"];
}

// set database connection constants
define('DB_PASS',$_SESSION["servers"][$server]["password"]);
define('DB_NAME',$_SESSION["servers"][$server]["dbname"]);
define('DB_USER',$_SESSION["servers"][$server]["username"]);
define('DB_HOST',$_SESSION["servers"][$server]["hostname"]);
define('DB_TYPE',$_SESSION["servers"][$server]["type"]);
define('DB_TABLE',$_SESSION["servers"][$server]["tablename"]);

// if not the live version, create a label for the version we're running
if (DEVMODE == true) {
	define('DEVINFO',"<b>Development:</b> revision v".VERSION);
}

// register class autoloader function
spl_autoload_register("ClassAutoloader");

// purge expired chart cache images
$cachedir = dir(CHART_CACHE);
while (($img = $cachedir->read()) !== false) {
	if ($img != "." && $img != ".." && substr($img,-4,4) == ".png") {
		if (filemtime(CHART_CACHE."/{$img}") < (time()-CHART_CACHE_EXPIRE)) {
			unlink(CHART_CACHE."/{$img}");	
		}
	}
}

// create router and run requested controller
$router = new Router();
$router->setPath(APP_PATH."/controllers");
$router->loader();

/**
 * Class autoloader function saves having to manually include each class and model file.
 * 
 * @param string $class		- class or model
 * 
 * @return void
 */
function ClassAutoloader($class) {
	foreach (array("classes","models") as $dir) {
		if (file_exists(APP_PATH."/{$dir}/{$class}.php")) {
			require_once(APP_PATH."/{$dir}/{$class}.php");
		}
	}
}

?>
