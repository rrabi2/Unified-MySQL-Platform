<?php
include 'common.php';

// If user is not logged in redirect to login page
if (!$user->is_logged_in())
{
	$_SESSION['REDIRECT_AFTER_LOGIN'] = 'invoices.php';
	header('location: user_login.php');
	exit;
}

if (!isset($_GET['PAGE']) || $_GET['PAGE'] == 1)
{
	$OFFSET = 0;
	$PAGE = 1;
}
else
{
	$PAGE = intval($_GET['PAGE']);
	$OFFSET = ($PAGE - 1) * $system->SETTINGS['perpage'];
}

// count the pages
$query = "SELECT COUNT(useracc_id) As COUNT  FROM " . $DBPrefix . "useraccounts
    WHERE user_id = " . $user->user_data['id'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$TOTALINVOICES = mysql_result($res, 0, 'COUNT');
$PAGES = ($TOTALINVOICES == 0) ? 1 : ceil($TOTALINVOICES / $system->SETTINGS['perpage']);

// get this page of data
$query = "SELECT * FROM " . $DBPrefix . "useraccounts
    WHERE user_id = " . $user->user_data['id'] . "
	LIMIT " . intval($OFFSET) . "," . $system->SETTINGS['perpage'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

while ($row = mysql_fetch_assoc($res))
{
	if ($row['total'] > 0)
	{
		$DATE = $row['date'] + $system->tdiff;

		// build invoice info
		$info = '';
		$auc_id = false;
		if ($row['setup'] != 0)
		{
			$info .= $MSG['432'] . ' ' . $system->print_money($row['setup']) . '<br>';
			$auc_id = true;
		}
		if ($row['featured'] != 0)
		{
			$info .= $MSG['433'] . ' ' . $system->print_money($row['featured']) . '<br>';
			$auc_id = true;
		}
		if ($row['bold'] != 0)
		{
			$info .= $MSG['439'] . ' ' . $system->print_money($row['bold']) . '<br>';
			$auc_id = true;
		}
		if ($row['highlighted'] != 0)
		{
			$info .= $MSG['434'] . ' ' . $system->print_money($row['highlighted']) . '<br>';
			$auc_id = true;
		}
		if ($row['subtitle'] != 0)
		{
			$info .= $MSG['803'] . ' ' . $system->print_money($row['subtitle']) . '<br>';
			$auc_id = true;
		}
		if ($row['relist'] != 0)
		{
			$info .= $MSG['437'] . ' ' . $system->print_money($row['relist']) . '<br>';
			$auc_id = true;
		}
		if ($row['reserve'] != 0)
		{
			$info .= $MSG['440'] . ' ' . $system->print_money($row['reserve']) . '<br>';
			$auc_id = true;
		}
		if ($row['buynow'] != 0)
		{
			$info .= $MSG['436'] . ' ' . $system->print_money($row['buynow']) . '<br>';
			$auc_id = true;
		}
		if ($row['image'] != 0)
		{
			$info .= $MSG['435'] . ' ' . $system->print_money($row['image']) . '<br>';
			$auc_id = true;
		}
		if ($row['extcat'] != 0)
		{
			$info .= $MSG['804'] . ' ' . $system->print_money($row['extcat']) . '<br>';
			$auc_id = true;
		}
		if ($row['signup'] != 0)
		{
			$info .= $MSG['768'] . ' ' . $system->print_money($row['signup']) . '<br>';
		}
		if ($row['buyer'] != 0)
		{
			$info .= $MSG['775'] . ' ' . $system->print_money($row['buyer']) . '<br>';
			$auc_id = true;
		}
		if ($row['finalval'] != 0)
		{
			$info .= $MSG['791'] . ' ' . $system->print_money($row['finalval']) . '<br>';
			$auc_id = true;
		}
		if ($row['balance'] != 0)
		{
			$info .= $MSG['935'] . ' ' . $system->print_money($row['balance']) . '<br>';
		}

		if ($auc_id)
		{
			$info = '<strong>' . $MSG['1034'] . ': ' . $row['auc_id'] . '</strong><br>' . $info;
		}

		$template->assign_block_vars('topay', array(
				'INVOICE' => $row['useracc_id'],
				'AUC_ID' => $row['auc_id'],
				'DATE' => ArrangeDateNoCorrection($DATE),
				'INFO' => $info,
				'TOTAL' => $system->print_money($row['total']),
				'PAID' => ($row['paid'] == 1), // true if paid
				'PDF' => $system->SETTINGS['siteurl'] . 'item_invoice.php?id=' . $row['auc_id']
				));
	}
}

// get pagenation
$PREV = intval($PAGE - 1);
$NEXT = intval($PAGE + 1);
if ($PAGES > 1)
{
	$LOW = $PAGE - 5;
	if ($LOW <= 0) $LOW = 1;
	$COUNTER = $LOW;
	while ($COUNTER <= $PAGES && $COUNTER < ($PAGE + 6))
	{
		$template->assign_block_vars('pages', array(
				'PAGE' => ($PAGE == $COUNTER) ? '<b>' . $COUNTER . '</b>' : '<a href="' . $system->SETTINGS['siteurl'] . 'outstanding.php?PAGE=' . $COUNTER . '"><u>' . $COUNTER . '</u></a>'
				));
		$COUNTER++;
	}
}

$_SESSION['INVOICE_RETURN'] = 'invoices.php';
$template->assign_vars(array(
		'CURRENCY' => $system->SETTINGS['currency'],

		'PREV' => ($PAGES > 1 && $PAGE > 1) ? '<a href="' . $system->SETTINGS['siteurl'] . 'outstanding.php?PAGE=' . $PREV . '"><u>' . $MSG['5119'] . '</u></a>&nbsp;&nbsp;' : '',
		'NEXT' => ($PAGE < $PAGES) ? '<a href="' . $system->SETTINGS['siteurl'] . 'outstanding.php?PAGE=' . $NEXT . '"><u>' . $MSG['5120'] . '</u></a>' : '',
		'PAGE' => $PAGE,
		'PAGES' => $PAGES
		));

include 'header.php';
$TMP_usmenutitle = $MSG['1059'];
include $include_path . 'user_cp.php';
$template->set_filenames(array(
		'body' => 'invoices.tpl'
		));
$template->display('body');
include 'footer.php';
?>
