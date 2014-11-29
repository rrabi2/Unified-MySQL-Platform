

include 'common.php';

if (!$user->is_logged_in())
{
	$_SESSION['REDIRECT_AFTER_LOGIN'] = 'yourbids.php';
	header('location: user_login.php');
	exit;
}

// get active bids for this user
$query = "SELECT a.current_bid, a.id, a.title, a.ends, b.bid, b.quantity FROM " . $DBPrefix . "bids b
		LEFT JOIN " . $DBPrefix . "auctions a ON (a.id = b.auction)
		WHERE a.closed = 0 AND b.bidder = " . $user->user_data['id'] . "
		AND a.bn_only = 'n' ORDER BY a.ends ASC, b.bid DESC";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$idcheck = array();
$auctions_count = 0;
while ($row = mysql_fetch_assoc($res))
{
	if (!in_array($row['id'], $idcheck))
	{
		// prepare some data
		$bgColor = (!($auctions_count % 2)) ? '' : 'class="alt-row"';

		// Outbidded or winning bid
		if ($row['current_bid'] != $row['bid']) $bgColor = 'style="background-color:#FFFF00;"';

		$auctions_count++;
		$idcheck[] = $row['id'];

		$template->assign_block_vars('bids', array(
				'BGCOLOUR' => $bgColor,
				'ID' => $row['id'],
				'TITLE' => $row['title'],
				'BID' => $system->print_money($row['bid']),
				'QTY' => $row['quantity'],
				'TIMELEFT' => FormatTimeLeft($row['ends'] - time()),
				'CBID' => $system->print_money($row['current_bid'])
				));
	}
}

$template->assign_vars(array(
		'NUM_BIDS' => $auctions_count
		));

include 'header.php';
$TMP_usmenutitle = $MSG['620'];
include $include_path . 'user_cp.php';
$template->set_filenames(array(
		'body' => 'yourbids.tpl'
		));
$template->display('body');
include 'footer.php';
?>
