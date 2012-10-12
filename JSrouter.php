<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Ernesto J Rodriguez
 * Date: 10/11/12
 * Time: 6:27 PM
 */
if(!isset($_SESSION)) 
{
	session_name('GaiaEHR');
	session_start();
	session_cache_limiter('private');
}

include_once($_SESSION['root'] . '/dataProvider/i18nRouter.php');
include_once($_SESSION['root'] . '/dataProvider/ACL.php');
include_once($_SESSION['root'] . '/dataProvider/User.php');
include_once($_SESSION['root'] . '/dataProvider/Globals.php');

$i18n = i18nRouter::getTranslation();
$acl = new ACL();
$perms = array();

/*
 * Look for user permissions and pass it to a PHP variable.
 * This variable will be used in JavaScript code
 * look at it as a PHP to JavaScript variable conversion.
 */
foreach($acl->getAllUserPermsAccess() AS $perm)
{
	$perms[$perm['perm']] = $perm['value'];
}

$user = new User();
$userData = $user->getCurrentUserBasicData();

/*
 * Pass all the PHP to JavaScript
 */
header('Content-Type: text/javascript');
print 'acl = '. json_encode($perms).';';
print 'i18n = '. json_encode($i18n).';';
print 'user = '. json_encode($userData).';';
print 'settings.site_url = "'. Globals::setGlobals().'";';