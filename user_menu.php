
// Connect to sql server & inizialize configuration variables
include 'common.php';

// If user is not logged in redirect to login page
if (!$user->is_logged_in())
{
	header('location: user_login.php');
	exit;
}

function get_reminders($secid)
{
	global $DBPrefix, $system;
	$data = array();
	// get number of new messages
	$query = "SELECT COUNT(*) AS total FROM " . $DBPrefix . "messages
			WHERE isread = 0 AND sentto = " . $secid;
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$data[] = mysql_result($res, 0, 'total');
	// get number of pending feedback
	$query = "SELECT COUNT(DISTINCT a.auction) AS total FROM " . $DBPrefix . "winners a
			LEFT JOIN " . $DBPrefix . "auctions b ON (a.auction = b.id)
			WHERE (b.closed = 1 OR b.bn_only = 'y') AND b.suspended = 0
			AND ((a.seller = " . $secid . " AND a.feedback_sel = 0)
			OR (a.winner = " . $secid . " AND a.feedback_win = 0))";
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$data[] = mysql_result($res, 0, 'total');
	// get auctions still requiring payment
	$query = "SELECT COUNT(DISTINCT id) AS total FROM " . $DBPrefix . "winners
			WHERE paid = 0 AND winner = " . $secid;
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$data[] = mysql_result($res, 0, 'total');
	// get auctions ending soon
	$query = "SELECT COUNT(DISTINCT b.auction) AS total FROM " . $DBPrefix . "bids b
			LEFT JOIN " . $DBPrefix . "auctions a ON (b.auction = a.id)
			WHERE b.bidder = " . $secid . " AND a.ends <= " . (time() + (3600 * 24)) . "
			AND a.closed = 0 GROUP BY b.auction";
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$data[] = (mysql_num_rows($res) > 0) ? mysql_result($res, 0, 'total') : 0;
	// get outbid auctions
	$query = "SELECT a.current_bid, a.id, a.title, a.ends, b.bid FROM " . $DBPrefix . "auctions a, " . $DBPrefix . "bids b
			WHERE a.id = b.auction AND a.closed = 0 AND b.bidder = " . $secid . "
			AND a.bn_only = 'n' ORDER BY a.ends ASC, b.bidwhen DESC";
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$idcheck = array();
	$auctions_count = 0;
	while ($row = mysql_fetch_assoc($res))
	{
		if (!in_array($row['id'], $idcheck))
		{
			// Outbidded or winning bid
			if ($row['current_bid'] != $row['bid']) $auctions_count++;;
			$idcheck[] = $row['id'];
		}
	}
	$data[] = $auctions_count;

	return $data;
}

// Send buyer's request to the administrator
if (isset($_POST['requesttoadmin']))
{
	$emailer = new email_handler();
	$emailer->assign_vars(array(
			'NAME' => $user->user_data['name'],
			'NICK' => $user->user_data['nick'],
			'EMAIL' => $user->user_data['email'],
			'ID' => $user->user_data['id']
			));
	$emailer->email_sender($system->SETTINGS['adminmail'], 'buyer_request.inc.php', $MSG['820']);
	$_SESSION['TMP_MSG'] = $MSG['25_0142'];
}

$cptab = (isset($_GET['cptab'])) ? $_GET['cptab'] : '';

switch ($cptab)
{
	default:
	case 'summary':
		$_SESSION['cptab'] = 'summary';
		break;
	case 'account':
		$_SESSION['cptab'] = 'account';
		break;
	case 'selling':
		$_SESSION['cptab'] = 'selling';
		break;
	case 'buying':
		$_SESSION['cptab'] = 'buying';
		break;
}

switch ($_SESSION['cptab'])
{
	default:
	case 'summary':
		$reminders = get_reminders($user->user_data['id']);
		$template->assign_vars(array(
				'NEWMESSAGES' => ($reminders[0] > 0) ? $reminders[0] . ' ' . $MSG['508'] . ' (<a href="' . $system->SETTINGS['siteurl'] . 'mail.php">' . $MSG['5295'] . '</a>)<br>' : '',
				'FBTOLEAVE' => ($reminders[1] > 0) ? $reminders[1] . $MSG['072'] . ' (<a href="' . $system->SETTINGS['siteurl'] . 'buysellnofeedback.php">' . $MSG['5295'] . '</a>)<br>' : '',
				'TO_PAY' => ($reminders[2] > 0) ? sprintf($MSG['792'], $reminders[2]) . ' (<a href="' . $system->SETTINGS['siteurl'] . 'outstanding.php">' . $MSG['5295'] . '</a>)<br>' : '',
				'BENDING_SOON' => ($reminders[3] > 0) ? $reminders[3] . $MSG['793'] . ' (<a href="' . $system->SETTINGS['siteurl'] . 'yourbids.php">' . $MSG['5295'] . '</a>)<br>' : '',
				'BOUTBID' => ($reminders[4] > 0) ? sprintf($MSG['794'], $reminders[4]) . ' (<a href="' . $system->SETTINGS['siteurl'] . 'yourbids.php">' . $MSG['5295'] . '</a>)<br>' : '',
				'NO_REMINDERS' => (($reminders[0] + $reminders[1] + $reminders[2] + $reminders[3] + $reminders[4]) == 0) ? $MSG['510'] : '',
				));
		break;
	case 'account':
		$reminders = get_reminders($user->user_data['id']);
		$template->assign_vars(array(
				'NEWMESSAGES' => ($reminders[0] > 0) ? '( ' . $reminders[0] . ' ' . $MSG['508'] . ' )' : '',
				'FBTOLEAVE' => ($reminders[1] > 0) ? '( ' . $reminders[1] . $MSG['072'] . ' )' : ''
				));
		break;
	case 'selling':
		break;
	case 'buying':
		break;
}

$template->assign_vars(array(
		'B_CANSELL' => ($user->can_sell),

		'TMPMSG' => (isset($_SESSION['TMP_MSG'])) ? $_SESSION['TMP_MSG'] : '',
		'THISPAGE' => $_SESSION['cptab']
		));

include 'header.php';
include $include_path . 'user_cp.php';
$template->set_filenames(array(
		'body' => 'user_menu.tpl'
		));
$template->display('body');
include 'footer.php';
unset($_SESSION['TMP_MSG']);
?>
