
include 'common.php';
include $include_path . 'membertypes.inc.php';

foreach ($membertypes as $idm => $memtypearr)
{
	$memtypesarr[$memtypearr['feedbacks']] = $memtypearr;
}
ksort($memtypesarr, SORT_NUMERIC);

if (!$user->is_logged_in())
{
	$_SESSION['REDIRECT_AFTER_LOGIN'] = 'yourfeedback.php';
	header('location: user_login.php');
	exit;
}

$i = 0;
foreach ($memtypesarr as $k => $l)
{
	if ($k >= $user->user_data['rate_sum'] || $i++ == (count($memtypesarr) - 1))
	{
		$TPL_rate_ratio_value = '<img src="' . $system->SETTINGS['siteurl'] . 'images/icons/' . $l['icon'] . '" alt="' . $l['icon'] . '" class="fbstar">';
		break;
	}
}

$page = (!isset($_GET['pg']) || $_GET['pg'] == 0) ? $_GET['pg'] : 1;
$left_limit = ($page - 1) * $system->SETTINGS['perpage'];

$query = "SELECT count(*) FROM " . $DBPrefix . "feedbacks WHERE rated_user_id = " . $user->user_data['id'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$total = mysql_result($res, 0);
// get number of pages
$pages = ceil($total / $system->SETTINGS['perpage']);

$left_limit = ($left_limit < 0) ? 0 : $left_limit;

$query = "SELECT f.*, a.title FROM " . $DBPrefix . "feedbacks f
		LEFT OUTER JOIN " . $DBPrefix . "auctions a
		ON a.id = f.auction_id
		WHERE rated_user_id = " . $user->user_data['id'] . "
		ORDER by feedbackdate DESC
		LIMIT $left_limit, " . $system->SETTINGS['perpage'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$i = 0;
$feed_disp = array();
while ($arrfeed = mysql_fetch_assoc($res))
{
	$query = "SELECT id, rate_num, rate_sum FROM " . $DBPrefix . "users WHERE nick = '" . $arrfeed['rater_user_nick'] . "'";
	$result = mysql_query($query);
	$system->check_mysql($result, $query, __LINE__, __FILE__);
	$usarr = mysql_fetch_array($result);
	$j = 0;
	foreach ($memtypesarr as $k => $l)
	{
		if ($k >= $usarr['rate_sum'] || $j++ == (count($memtypesarr) - 1))
		{
			$usicon = '<img src="' . $system->SETTINGS['siteurl'] . 'images/icons/' . $l['icon'] . '" alt="' . $l['icon'] . '" class="fbstar">';
			break;
		}
	}
	switch ($arrfeed['rate'])
	{
		case 1: $uimg = $system->SETTINGS['siteurl'] . 'images/positive.png';
			break;
		case - 1: $uimg = $system->SETTINGS['siteurl'] . 'images/negative.png';
			break;
		case 0: $uimg = $system->SETTINGS['siteurl'] . 'images/neutral.png';
			break;
	}
	$template->assign_block_vars('fbs', array(
			'BGCOLOUR' => (!(($i + 1) % 2)) ? '' : 'class="alt-row"',
			'IMG' => $uimg,
			'USFLINK' => 'profile.php?user_id=' . $usarr['id'] . '&auction_id=' . $arrfeed['auction_id'],
			'USERNAME' => $arrfeed['rater_user_nick'],
			'USFEED' => $usarr['rate_sum'],
			'USICON' => (isset($usicon)) ? $usicon : '',
			'FBDATE' => FormatDate($arrfeed['feedbackdate']),
			'AUCTIONURL' => ($arrfeed['title']) ? '<a href="item.php?id=' . $arrfeed['auction_id'] . '">' . $arrfeed['title'] . '</a>' : $MSG['113'] . $arrfeed['auction_id'],
			'FEEDBACK' => nl2br(stripslashes($arrfeed['feedback']))
			));

	$i++;
}

$firstpage = (($page - 5) <= 0) ? 1 : ($page - 5);
$lastpage = (($page + 5) > $pages) ? $pages : ($page + 5);
$backpage = (($page - 1) <= 0) ? 1 : ($page - 1);
$nextpage = (($page + 1) > $pages) ? $pages : ($page + 1);
$echofeed = ($page == 1) ? '' : '<a href="yourfeedback.php">&laquo;</a> <a href="yourfeedback.php?pg=' . $backpage . '"><</a> ';
for ($ind2 = $firstpage; $ind2 <= $lastpage; $ind2++)
{
	if ($page != $ind2)
	{
		$echofeed .= '<a href="yourfeedback.php?pg=' . $ind2 . '">' . $ind2 . '</a>';
	}
	else
	{
		$echofeed .= $ind2;
	}
	if ($ind2 != $lastpage)
	{
		$echofeed .= ' | ';
	}
}
$echofeed .= ($thispage == $pages || $pages == 0) ? '' : ' <a href="yourfeedback.php?pg=' . $nextpage . '">></a> <a href="yourfeedback.php?pg=' . $pages . '">&raquo;</a>';

$template->assign_vars(array(
		'USERNICK' => $user->user_data['nick'],
		'USERFB' => $user->user_data['rate_sum'],
		'USERFBIMG' => (isset($TPL_rate_ratio_value)) ? $TPL_rate_ratio_value : '',
		'PAGENATION' => $echofeed,
		'BGCOLOUR' => (!(($i + 1) % 2)) ? '' : 'class="alt-row"'
		));

include 'header.php';
$TMP_usmenutitle = $MSG['25_0223'];
include $include_path . 'user_cp.php';
$template->set_filenames(array(
		'body' => 'yourfeedback.tpl'
		));
$template->display('body');
include 'footer.php';
?>
