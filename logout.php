<?php

include 'common.php';

$query = "DELETE from " . $DBPrefix . "online WHERE SESSION = 'uId-" . $_SESSION['WEBID_LOGGED_IN'] . "'";
$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);

unset($_SESSION['WEBID_LOGGED_IN'], $_SESSION['WEBID_LOGGED_NUMBER'], $_SESSION['WEBID_LOGGED_PASS']);
if (isset($_COOKIE['WEBID_RM_ID']))
{
	$query = "DELETE FROM " . $DBPrefix . "rememberme WHERE hashkey = '" . strip_non_an_chars($_COOKIE['WEBID_RM_ID']) . "'";
	$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
	setcookie('WEBID_RM_ID', '', time() - 3600);
}

header('location: index.php');
exit;
?>
