<?php
	/*
	 * CONFIG
	 * - v1 - 
	 * - v2 - updated BASE CONFIG, error_reporting based on PROJECTSTATUS
	 * - v3 - added staging option
	 * - v3.1 - BUGFIX in staging option
	 */

	/* DEVELOPMENT CONFIG */
		// DEFINE('PROJECTSTATUS','LIVE');
		// DEFINE('PROJECTSTATUS','STAGING');
		DEFINE('PROJECTSTATUS','DEV');
	/* DEVELOPMENT CONFIG */

	/* TIMEZONE CONFIG */
	$timezone = "Asia/Calcutta";
	if(function_exists('date_default_timezone_set')) date_default_timezone_set($timezone);
	/* TIMEZONE CONFIG */

	if(PROJECTSTATUS=="LIVE"){
		error_reporting(0);
		DEFINE('BASE_URL','http://www.effwa.com');
		DEFINE('ADMIN_EMAIL','info@effwa.com');
	} else if(PROJECTSTATUS=="STAGING"){
		error_reporting(E_ALL);
		DEFINE('BASE_URL','http://www.shareittofriends.com/demo/occasioncakeshop');
		DEFINE('ADMIN_EMAIL','info@occasioncakeshop.com');
	} else { // DEFAULT TO DEV
		error_reporting(E_ALL);
		DEFINE('BASE_URL','http://192.168.1.113/staging/php/dirty-clean');
		DEFINE('ADMIN_EMAIL','info@occasioncakeshop.com');
	}

	/* BASE CONFIG */
		
		DEFINE('SITE_NAME','Dirty Clean');
		DEFINE('TITLE','Administrator Panel | '.SITE_NAME);
		DEFINE('PREFIX','dc_');
		DEFINE('COPYRIGHT',date("Y"));
		DEFINE('LOGO', BASE_URL.'/images/logo.png');
	/* BASE CONFIG */
?>