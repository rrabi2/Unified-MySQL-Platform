

include 'common.php';

if (($system->SETTINGS['contactseller'] == 'logged' && !$user->is_logged_in()) || $system->SETTINGS['contactseller'] == 'never')
{
	if (isset($_SESSION['REDIRECT_AFTER_LOGIN']))
	{
		header('location: ' . $_SESSION['REDIRECT_AFTER_LOGIN']);
	}
	else
	{
		header('location: index.php');
	}
}

if (!isset($_POST['auction_id']) && !isset($_GET['auction_id']))
{
	$auction_id = $_SESSION['CURRENT_ITEM'];
}
else
{
	$auction_id = intval($_GET['auction_id']);
}
$_SESSION['CURRENT_ITEM'] = $auction_id;

// Get item description
$query = "SELECT a.user, a.title, u.nick, u.email FROM " . $DBPrefix . "auctions a
		LEFT JOIN " . $DBPrefix . "users u ON (u.id = a.user)
		WHERE a.id = " . intval($auction_id);
$result = mysql_query($query);
$system->check_mysql($result, $query, __LINE__, __FILE__);

if (mysql_num_rows($result) == 0)
{
	$TPL_error_text = $ERR_606;
}
else
{
	$auction_data = mysql_fetch_assoc($result);
	$seller_id = $auction_data['user'];
	$item_title = $auction_data['title'];
	$seller_nick = $auction_data['nick'];
	$seller_email = $auction_data['email'];
}

if (isset($_POST['action']) || !empty($_POST['action']))
{
	$cleaned_question = $system->cleanvars($_POST['sender_question']);
	if ($system->SETTINGS['wordsfilter'] == 'y')
	{
		$cleaned_question = $system->filter($cleaned_question);
	}

	// Check errors
	if (isset($_POST['action']) && (!isset($_POST['sender_name']) || !isset($_POST['sender_email']) || empty($seller_nick) || empty($seller_email)))
	{
		$TPL_error_text = $ERR_032;
	}

	if (empty($cleaned_question))
	{
		$TPL_error_text = $ERR_031;
	}

	if (isset($_POST['action']) && (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i', $_POST['sender_email']) || !preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i', $seller_email)))
	{
		$TPL_error_text = $ERR_008;
	}
	if (empty($TPL_error_text))
	{
		$mes = $MSG['337'] . ': <i>' . $seller_nick . '</i><br><br>';
		$emailer = new email_handler();
		$emailer->assign_vars(array(
				'SENDER_NAME' => $_POST['sender_name'],
				'SENDER_QUESTION' => $cleaned_question,
				'SENDER_EMAIL' => $_POST['sender_email'],
				'SITENAME' => $system->SETTINGS['sitename'],
				'SITEURL' => $system->SETTINGS['siteurl'],
				'AID' => $auction_id,
				'TITLE' => $item_title,
				'SELLER_NICK' => $seller_nick
				));
		$item_title = $system->uncleanvars($item_title);
		$subject = $MSG['335'] . ' ' . $system->SETTINGS['sitename'] . ' ' . $MSG['336'] . ' ' . $item_title;
		$from_id = (!$user->logged_in) ? $_POST['sender_email'] : $user->user_data['id'];
		$id_type = (!$user->logged_in) ? 'fromemail' : 'sentfrom';
		$emailer->email_uid = $seller_id;
		$emailer->email_sender($seller_email, 'send_email.inc.php', $subject);
		$query = "INSERT INTO " . $DBPrefix . "messages (sentto, " . $id_type . ", sentat, message, subject, question)
				VALUES (" . $seller_id . ", '" . $from_id . "', '" . time() . "', '" . $cleaned_question . "', '" . $system->cleanvars(sprintf($MSG['651'], $item_title)) . "', " . $auction_id . ")";
		$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
	}
}

$template->assign_vars(array(
		'MESSAGE' => (isset($mes)) ? $mes : '',
		'ERROR' => (isset($TPL_error_text)) ? $TPL_error_text : '',
		'AUCT_ID' => $auction_id,
		'SELLER_NICK' => $seller_nick,
		'SELLER_EMAIL' => $seller_email,
		'SELLER_QUESTION' => (isset($_POST['sender_question'])) ? $_POST['sender_question'] : '',
		'ITEM_TITLE' => $item_title,
		'EMAIL' => ($user->logged_in) ? $user->user_data['email'] : ''
		));

include 'header.php';
$template->set_filenames(array(
		'body' => 'send_email.tpl'
		));
$template->display('body');
include 'footer.php';
?>
