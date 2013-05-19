<?php

/**
 * Ensure that all CDRs have a unique ID and create unique IDs for those that
 * don't. You should only really run this once on an existing table of CDRs.
 * After that you need to set cdr_mysql to create unique IDs from thereon.
 *
 * @package	AGCDR
 * @author	SBF
 * @copyright	2011
 */

class FixUniqueIDs extends BaseRoutine {

	/**
	 * Construct via parent.
	 *
	 * @access public
	 */
	public function __construct($dir,$params=false) {
		parent::__construct($dir,$params);
	}

	/**
	 * Main execution function.
	 *
	 * @return void
	 * @access public
	 */
	public function go() {

		// banner
		print "Checking all your CDRs have a unique ID ...\n";



	}



}

?>