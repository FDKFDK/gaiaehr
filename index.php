<?php
/* GaiaEHR Starter
 * 
 * Description: This will start the application, if no sites are found
 * in the sites directory run the setup wizard, if a directory is found
 * run the login screen. When the logon is submitted it will validate
 * the user and start the main application
 * 
 * Author: GI Technologies, 2011
 * modified: Ernesto J Rodriguez, Nov 7, 2011
 *
 * Ver: 0.0.3
 * 
 */
/**
 * Startup the SESSION
 * This will change in the future.
 * Maybe implement a SESSION Manager against the database.
 */
session_name ( 'GaiaEHR' );
session_start();
session_cache_limiter('private');
define('_GaiaEXEC', 1);
/*
 * Startup the registry
 * This contains SESSION Variables to use in the application
 * and mobile_detect class is used to detect mobile browsers.
 */
include_once('registry.php');
include_once('classes/Mobile_Detect.php');
$mobile = new Mobile_Detect();
/**
 * set the site using the url parameter site, or default if not given
 */
$site = (isset($_GET['site']) ? $_GET['site'] : 'default');


if(file_exists('sites/'.$site.'/conf.php'))
{
	include_once('sites/'.$site.'/conf.php');
	$_SESSION['site']['localization'] = (isset($_SESSION['site']['localization']) && ($_SESSION['site']['default_localization'] != $_SESSION['site']['localization'])) ? $_SESSION['site']['localization'] : 'en_US';
};
/**
 * Make the auth process
 * lets check for 4 things to allow the user in
 * 1. $_SESSION['user'] is set (this helps to app clean of PHP NOTICES)
 * 2. $_SESSION['user']['auth'] is true (check if the user is authorized)
 * 3. $_SESSION['user']['site'] is $site ($site == $_GET['site] or 'default')
 * 4. $_SESSION['inactive']['life'] is less than $_SESSION['inactive']['time'] (to make sure ths user hasn't been out for a long time)
 * 
 */
if( 
	isset($_SESSION['user']) && 
	$_SESSION['user']['auth'] == true && 
	$_SESSION['user']['site'] == $site && 
	$_SESSION['inactive']['life'] < $_SESSION['inactive']['time'])
{


	/**
     * if mobile go to mobile app, else go to app
     */
    if($_SESSION['site']['checkInMode'])
    {
        include_once('checkin/checkin.php');
    }
    elseif($mobile->isMobile()) 
    {
	    include_once('_app_m.php');
    }
    else
    {
        include_once('dataProvider/Globals.php');
        Globals::setGlobals();
        include_once('_app.php');
    }

} 
else // Make the logon process or Setup process
{
	/**
     * If no directory is found inside sites dir run the setup wizard,
     * if a directory is found inside sites dir run the logon screen
     */
	if($_SESSION['sites']['count'] < 1)
	{
		unset($_SESSION['site']);
		include_once('_install.php');
	} 
	else 
	{
        // if mobile go to mobile app, else go to app
        if ($mobile->isMobile()) 
        {
            include_once('_login_m.php');
        }
        else
        {
            include_once('_login.php');
        }
	}
}

$_SESSION['inactive']['timeout'] = time();