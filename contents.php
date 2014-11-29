<?php

include 'common.php';

switch ($_GET['show'])
{
	case 'aboutus':
		$TITLE = $MSG['5085'];
		$CONTENT = stripslashes($system->SETTINGS['aboutustext']);
		break;
	case 'terms':
		$TITLE = $MSG['5086'];
		$CONTENT = stripslashes($system->SETTINGS['termstext']);
		break;
	case 'priv':
		$TITLE = $MSG['401'];
		$CONTENT = stripslashes($system->SETTINGS['privacypolicytext']);
		break;
}

$template->assign_vars(array(
		'TITLE' => $TITLE,
		'CONTENT' => $CONTENT
		));

include 'header.php';
$template->set_filenames(array(
		'body' => 'contents.tpl'
		));
$template->display('body');
include 'footer.php';
?>
