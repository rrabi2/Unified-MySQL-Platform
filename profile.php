

include 'common.php';
include $include_path . 'dates.inc.php';
include $include_path . 'membertypes.inc.php';

foreach ($membertypes as $idm => $memtypearr)
{
	$memtypesarr[$memtypearr['feedbacks']] = $memtypearr;
}
ksort($memtypesarr, SORT_NUMERIC);

if(!isset($_GET['user_id']))
{
	$_GET['user_id'] = $user->user_data['id'];
}

if (!empty($_GET['user_id']) && is_string($_GET['user_id']))
{
	$sql = "SELECT * FROM " . $DBPrefix . "users WHERE nick = '" . $system->cleanvars($_GET['user_id']) . "'";
	$res = mysql_query($sql);
	$system->check_mysql($res, $sql, __LINE__, __FILE__);
}

if (!empty($_GET['user_id']))
{
	$sql = "SELECT * FROM " . $DBPrefix . "users WHERE id = " . intval($_GET['user_id']);
	$res = mysql_query($sql);
	$system->check_mysql($res, $sql, __LINE__, __FILE__);
}

if (@mysql_num_rows($res) == 1)
{
	$arr = mysql_fetch_assoc($res);
	$TPL_user_id = $arr['id'];
	$TPL_rate_ratio_value = '';
	foreach ($memtypesarr as $k => $l)
	{
		if ($k >= $arr['rate_sum'] || $i++ == (count($memtypesarr) - 1))
		{
			$TPL_rate_ratio_value = '<img src="' . $system->SETTINGS['siteurl'] . 'images/icons/' . $l['icon'] . '" alt="' . $l['icon'] . '" class="fbstar">';
			break;
		}
	}
	$sql = "SELECT f.*, a.user FROM " . $DBPrefix . "feedbacks f
			LEFT JOIN " . $DBPrefix . "auctions a ON (a.id = f.auction_id)
			WHERE f.rated_user_id = " . $TPL_user_id;
	$res_ = mysql_query($sql);
	$system->check_mysql($res_, $sql, __LINE__, __FILE__);

	$total_fb = 0;
	$fb = array(-1 => 0, 0 => 0, 1 => 0);
	$fb_as_seller = array(-1 => 0, 0 => 0, 1 => 0);
	$fb_as_buyer = array(-1 => 0, 0 => 0, 1 => 0);
	$fb_last_year = array(-1 => 0, 0 => 0, 1 => 0);
	$fb_last_3month = array(-1 => 0, 0 => 0, 1 => 0);
	$fb_last_month = array(-1 => 0, 0 => 0, 1 => 0);
	if (mysql_num_rows($res_) > 0)
	{
		while ($ratesum = mysql_fetch_array($res_))
		{
			$fb[$ratesum['rate']]++;
			$total_fb++;
			if ($ratesum['user'] == $TPL_user_id)
			{
				$fb_as_seller[$ratesum['rate']]++;
			}
			else
			{
				$fb_as_buyer[$ratesum['rate']]++;
			}
			if ($ratesum['feedbackdate'] > time() - (3600 * 24 * 365))
			{
				$fb_last_year[$ratesum['rate']]++;
			}
			if ($ratesum['feedbackdate'] > time() - (3600 * 24 * 90))
			{
				$fb_last_3month[$ratesum['rate']]++;
			}
			if ($ratesum['feedbackdate'] > time() - (3600 * 24 * 30))
			{
				$fb_last_month[$ratesum['rate']]++;
			}
		}
	}

	$DATE = $arr['reg_date'] + $system->tdiff;
	$mth = 'MON_0'.gmdate('m', $DATE);

	$feedback_rate = ($arr['rate_sum'] == 0) ? 1 : $arr['rate_sum'];
	$feedback_rate = ($feedback_rate < 0) ? $feedback_rate * - 1 : $feedback_rate;
	$total_fb = ($total_fb < 1) ? 1 : $total_fb;
	$variables = array(
		'RATE_VAL' => $TPL_rate_ratio_value,
		'NUM_FB' => $arr['rate_num'],
		'SUM_FB' => $arr['rate_sum'],
		'FB_POS' => (isset($fb[1])) ? $MSG['500'] . $fb[1] . ' (' . ceil($fb[1] * 100 / $total_fb) . '%)<br>' : '',
		'FB_NEUT' => (isset($fb[0])) ? $MSG['499'] . $fb[0] . ' (' . ceil($fb[0] * 100 / $total_fb) . '%)<br>' : '',
		'FB_NEG' => (isset($fb[ - 1])) ? '<span style="color:red">' . $MSG['501'] . $fb[ - 1] . ' (' . ceil($fb[ - 1] * 100 / $total_fb) . '%)</span>' : '',
		'FB_SELLER_POS' => $fb_as_seller[1],
		'FB_BUYER_POS' => $fb_as_buyer[1],
		'FB_LASTYEAR_POS' => $fb_last_year[1],
		'FB_LAST3MONTH_POS' => $fb_last_3month[1],
		'FB_LASTMONTH_POS' => $fb_last_month[1],
		'FB_SELLER_NEUT' => $fb_as_seller[0],
		'FB_BUYER_NEUT' => $fb_as_buyer[0],
		'FB_LASTYEAR_NEUT' => $fb_last_year[0],
		'FB_LAST3MONTH_NEUT' => $fb_last_3month[0],
		'FB_LASTMONTH_NEUT' => $fb_last_month[0],
		'FB_SELLER_NEG' => $fb_as_seller[-1],
		'FB_BUYER_NEG' => $fb_as_buyer[-1],
		'FB_LASTYEAR_NEG' => $fb_last_year[-1],
		'FB_LAST3MONTH_NEG' => $fb_last_3month[-1],
		'FB_LASTMONTH_NEG' => $fb_last_month[-1],
		'REGSINCE' => $MSG[$mth].' '.gmdate('d, Y', $DATE),
		'COUNTRY' => $arr['country'],
		'AUCTION_ID' => (isset($_GET['auction_id'])) ? $_GET['auction_id'] : '',
		'USER' => $arr['nick'],
		'USER_ID' => $TPL_user_id,
		'B_VIEW' => true,
		'B_AUCID' => (isset($_GET['auction_id'])),
		'B_CONTACT' => (($system->SETTINGS['contactseller'] == 'always' || ($system->SETTINGS['contactseller'] == 'logged' && $user->logged_in)) && (!$user->logged_in || $user->user_data['id'] != $TPL_user_id))
		);
}
else
{
	$variables = array(
		'B_VIEW' => false,
		'MSG' => $ERR_025
		);
}

$template->assign_vars($variables);

include 'header.php';
$template->set_filenames(array(
		'body' => 'profile.tpl'
		));
$template->display('body');
include 'footer.php';
?>
