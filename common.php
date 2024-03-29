<?php
session_start();
$error_reporting = E_ALL^E_NOTICE;
// $error_reporting = E_ALL; // use this for debugging
define('InWeBid', 1);
define('TrackUserIPs', 1);

// file check & 
if(!@include('includes/config.inc.php'))
{
	$install_path = (!defined('InAdmin')) ? 'install/install.php' : '../install/install.php';
	header('location: ' . $install_path);
	exit;
}

$MD5_PREFIX = (!isset($MD5_PREFIX)) ? 'fhQYBpS5FNs4' : $MD5_PREFIX; // if the user didnt set a code
$include_path = $main_path . 'includes/'; 
$uploaded_path = 'uploaded/';
$upload_path = $main_path . $uploaded_path;

include $include_path . 'errors.inc.php'; //error handler functions
include $include_path . 'dates.inc.php';

// classes
include $include_path . 'class_db_handle.php';
include $include_path . 'functions_global.php';
include $include_path . 'class_email_handler.php';
include $include_path . 'class_MPTTcategories.php';
include $include_path . 'class_fees.php';
include $include_path . 'class_user.php';
include $include_path . 'template.php';

//$db = new db_handle();
$system = new global_class();
$template = new template();
$user = new user();
set_error_handler('WeBidErrorHandler', $error_reporting);

include $include_path . 'messages.inc.php';

// connect to the database
//$db->connect($DbHost, $DbUser, $DbPassword, $DbDatabase, $DBPrefix, $CHARSET);
// UNUSED AT THE MOMENT

// set DB charset
mysql_set_charset($CHARSET); # TEMP WHILE STILL USING MYSQL_

// add auction types
$system->SETTINGS['auction_types'] = array (
	1 => $MSG['1021'],
	2 => $MSG['1020']
);

// Atuomatically login user is necessary "Remember me" option
if (!$user->logged_in && isset($_COOKIE['WEBID_RM_ID']))
{
	$query = "SELECT userid FROM " . $DBPrefix . "rememberme WHERE hashkey = '" . mysql_escape_string($_COOKIE['WEBID_RM_ID']) . "'";
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	if (mysql_num_rows($res) > 0)
	{
		// generate a random unguessable token
		$_SESSION['csrftoken'] = md5(uniqid(rand(), true));
		$id = mysql_result($res, 0, 'userid');
		$query = "SELECT hash, password FROM " . $DBPrefix . "users WHERE id = " . $id;
		$res = mysql_query($query);
		$system->check_mysql($res, $query, __LINE__, __FILE__);
		$password = mysql_result($res, 0, 'password');
		$_SESSION['WEBID_LOGGED_IN'] 		= $id;
		$_SESSION['WEBID_LOGGED_NUMBER'] 	= strspn($password, mysql_result($res, 0, 'hash'));
		$_SESSION['WEBID_LOGGED_PASS'] 		= $password;
	}
}

if($user->logged_in)
{
	$system->ctime = time() + (($user->user_data['timecorrection'] + gmdate('I')) * 3600);
	$system->tdiff = ($user->user_data['timecorrection'] + gmdate('I')) * 3600;
}

$template->set_template();
?>