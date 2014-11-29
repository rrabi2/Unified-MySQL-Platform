

include 'common.php';

if ($system->SETTINGS['boards'] == 'n')
{
	header('location: index.php');
}

if (!$user->is_logged_in())
{
	$_SESSION['REDIRECT_AFTER_LOGIN'] = 'boards.php';
	header('location: user_login.php');
	exit;
}

// Retrieve message boards from the database
$query = "SELECT * FROM " . $DBPrefix . "community WHERE active = 1 ORDER BY name";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

while ($board = mysql_fetch_array($res))
{
	$template->assign_block_vars('boards', array(
			'NAME' => $board['name'],
			'ID' => $board['id'],
			'NUMMSG' => $board['messages'],
			'LASTMSG' => (!empty($board['lastmessage'])) ? FormatDate($board['lastmessage']) : '--'
			));
}

include 'header.php';
$template->set_filenames(array(
		'body' => 'boards.tpl'
		));
$template->display('body');
include 'footer.php';
?>
