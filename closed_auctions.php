
include 'common.php';

if (!empty($_GET['user_id']))
{
	$user_id = intval($_GET['user_id']);
}
elseif ($user->logged_in)
{
	$user_id = $user->user_data['id'];
}
else
{
	$_SESSION['REDIRECT_AFTER_LOGIN'] = 'closed_auctions.php';
	header('location: user_login.php');
	exit;
}

// check trying to access valid user id
$user->is_valid_user($user_id);

// get number of closed auctions for this user
$query = "SELECT count(id) AS auctions FROM " . $DBPrefix . "auctions
	  WHERE user = " . intval($user_id) . "
	  AND closed = 1";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$TOTALAUCTIONS = mysql_result($res, 0, 'auctions');

// Handle pagination
if (!isset($_GET['PAGE']) || $_GET['PAGE'] == 1 || $_GET['PAGE'] == '')
{
	$OFFSET = 0;
	$PAGE = 1;
}
else
{
	$PAGE = intval($_GET['PAGE']);
	$OFFSET = ($PAGE - 1) * $system->SETTINGS['perpage'];
}
$PAGES = ceil($TOTALAUCTIONS / $system->SETTINGS['perpage']);
if ($PAGES < 1) $PAGES = 1;

$query = "SELECT * FROM " . $DBPrefix . "auctions
		WHERE user = " . intval($user_id) . "
		AND closed = 1
		ORDER BY ends ASC LIMIT " . $OFFSET . ", " . $system->SETTINGS['perpage'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

while ($row = mysql_fetch_assoc($res))
{
	$bid = $row['current_bid'];
	$starting_price = $row['current_bid'];

	if (strlen($row['pict_url']) > 0)
	{
		$row['pict_url'] = $system->SETTINGS['siteurl'] . 'getthumb.php?w=' . $system->SETTINGS['thumb_show'] . '&fromfile=' . $uploaded_path . $row['id'] . '/' . $row['pict_url'];
	}
	else
	{
		$row['pict_url'] = get_lang_img('nopicture.gif');
	}

	// number of bids for this auction
	$query_ = "SELECT bid FROM " . $DBPrefix . "bids WHERE auction=" . $row['id'];
	$tmp_res = mysql_query($query_);
	$system->check_mysql($tmp_res, $query_, __LINE__, __FILE__);
	$num_bids = mysql_num_rows($tmp_res);

	$difference = time() - $row['ends'];
	$days_difference = intval($difference / 86400);
	$difference = $difference - ($days_difference * 86400);

	if (intval($difference / 3600) > 12) $days_difference++;

	$template->assign_block_vars('auctions', array(
			'BGCOLOUR' => (!($TOTALAUCTIONS % 2)) ? '' : 'class="alt-row"',
			'ID' => $row['id'],
			'PIC_URL' => $row['pict_url'],
			'TITLE' => $row['title'],
			'BNIMG' => get_lang_img(($row['bn_only'] == 'n') ? 'buy_it_now.gif' : 'bn_only.png'),
			'BNVALUE' => $row['buy_now'],
			'BNFORMAT' => $system->print_money($row['buy_now']),
			'BIDVALUE' => $row['minimum_bid'],
			'BIDFORMAT' => $system->print_money($row['minimum_bid']),
			'NUM_BIDS' => $num_bids,
			'TIMELEFT' => $days_difference . ' ' . $MSG['126a'],

			'B_BUY_NOW' => ($row['buy_now'] > 0 && ($row['bn_only'] == 'y' || $row['bn_only'] == 'n' && ($row['num_bids'] == 0 || ($row['reserve_price'] > 0 && $row['current_bid'] < $row['reserve_price'])))),
			'B_BNONLY' => ($row['bn_only'] == 'y')
			));

	$auctions_count++;
}

if ($auctions_count == 0)
{
	$template->assign_block_vars('no_auctions', array());
}

// get this user's nick
$query = "SELECT nick FROM " . $DBPrefix . "users WHERE id = " . $user_id;
$result = mysql_query($query);
$system->check_mysql($result, $query, __LINE__, __FILE__);
$TPL_user_nick = mysql_result($result, 0);

$LOW = $PAGE - 5;
if ($LOW <= 0) $LOW = 1;
$COUNTER = $LOW;
$pagenation = '';
while ($COUNTER <= $PAGES && $COUNTER < ($PAGE + 6))
{
	if ($PAGE == $COUNTER)
	{
		$pagenation .= '<b>' . $COUNTER . '</b>&nbsp;&nbsp;';
	}
	else
	{
		$pagenation .= '<a href="closed_auctions.php?PAGE=' . $COUNTER . '&user_id=' . $user_id . '"><u>' . $COUNTER . '</u></a>&nbsp;&nbsp;';
	}
	$COUNTER++;
}

$template->assign_vars(array(
		'B_MULPAG' => ($PAGES > 1),
		'B_NOTLAST' => ($PAGE < $PAGES),
		'B_NOTFIRST' => ($PAGE > 1),

		'USER_ID' => $user_id,
		'USERNAME' => $TPL_user_nick,
		'THUMBWIDTH' => $system->SETTINGS['thumb_show'],
		'NEXT' => intval($PAGE + 1),
		'PREV' => intval($PAGE - 1),
		'PAGE' => $PAGE,
		'PAGES' => $PAGES,
		'PAGENA' => $pagenation
		));

include 'header.php';
$template->set_filenames(array(
		'body' => 'auctions_closed.tpl'
		));
$template->display('body');
include 'footer.php';
?>