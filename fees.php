

include 'common.php';

if ($system->SETTINGS['fees'] == 'n')
{
	header('location: index.php');
	exit;
}

// get fees
$query = "SELECT * FROM " . $DBPrefix . "fees";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$setup = $buyer_fee = $endauc_fee = false;

$i = 0;
while ($row = mysql_fetch_array($res))
{
	if ($row['type'] == 'setup')
	{
		if ($row['fee_from'] != $row['fee_to'])
		{
			$setup = true;
			$template->assign_block_vars('setup_fees', array(
					'BGCOLOUR' => (!($i % 2)) ? '' : 'class="alt-row"',
					'FROM' => $system->print_money($row['fee_from']),
					'TO' => $system->print_money($row['fee_to']),
					'VALUE' => ($row['fee_type'] == 'flat') ? $system->print_money($row['value']) : $row['value'] . '%'
					));
		}
	}
	elseif ($row['type'] == 'buyer_fee')
	{
		if ($row['fee_from'] != $row['fee_to'])
		{
			$buyer_fee = true;
			$template->assign_block_vars('buyer_fee', array(
					'BGCOLOUR' => (!($i % 2)) ? '' : 'class="alt-row"',
					'FROM' => $system->print_money($row['fee_from']),
					'TO' => $system->print_money($row['fee_to']),
					'VALUE' => ($row['fee_type'] == 'flat') ? $system->print_money($row['value']) : $row['value'] . '%'
					));
		}
	}
	elseif ($row['type'] == 'endauc_fee')
	{
		if ($row['fee_from'] != $row['fee_to'])
		{
			$endauc_fee = true;
			$template->assign_block_vars('endauc_fee', array(
					'BGCOLOUR' => (!($i % 2)) ? '' : 'class="alt-row"',
					'FROM' => $system->print_money($row['fee_from']),
					'TO' => $system->print_money($row['fee_to']),
					'VALUE' => ($row['fee_type'] == 'flat') ? $system->print_money($row['value']) : $row['value'] . '%'
					));
		}
	}
	elseif ($row['type'] == 'signup_fee')
	{
		$template->assign_vars(array(
				'B_SIGNUP_FEE' => ($row['value'] > 0),
				'SIGNUP_FEE' => $system->print_money($row['value'])
				));
	}
	elseif ($row['type'] == 'hpfeat_fee')
	{
		$template->assign_vars(array(
				'B_HPFEAT_FEE' => ($row['value'] > 0),
				'HPFEAT_FEE' => $system->print_money($row['value'])
				));
	}
	elseif ($row['type'] == 'bolditem_fee')
	{
		$template->assign_vars(array(
				'B_BOLD_FEE' => ($row['value'] > 0),
				'BOLD_FEE' => $system->print_money($row['value'])
				));
	}
	elseif ($row['type'] == 'hlitem_fee')
	{
		$template->assign_vars(array(
				'B_HL_FEE' => ($row['value'] > 0),
				'HL_FEE' => $system->print_money($row['value'])
				));
	}
	elseif ($row['type'] == 'rp_fee')
	{
		$template->assign_vars(array(
				'B_RP_FEE' => ($row['value'] > 0),
				'RP_FEE' => $system->print_money($row['value'])
				));
	}
	elseif ($row['type'] == 'picture_fee')
	{
		$template->assign_vars(array(
				'B_PICTURE_FEE' => ($row['value'] > 0),
				'PICTURE_FEE' => $system->print_money($row['value'])
				));
	}
	elseif ($row['type'] == 'relist_fee')
	{
		$template->assign_vars(array(
				'B_RELIST_FEE' => ($row['value'] > 0),
				'RELIST_FEE' => $system->print_money($row['value'])
				));
	}
	elseif ($row['type'] == 'buyout_fee')
	{
		$template->assign_vars(array(
				'B_BUYNOW_FEE' => ($row['value'] > 0),
				'BUYNOW_FEE' => $system->print_money($row['value'])
				));
	}
	elseif ($row['type'] == 'excat_fee')
	{
		$template->assign_vars(array(
				'B_EXCAT_FEE' => ($row['value'] > 0),
				'EXCAT_FEE' => $system->print_money($row['value'])
				));
	}
	elseif ($row['type'] == 'subtitle_fee')
	{
		$template->assign_vars(array(
				'B_SUBTITLE_FEE' => ($row['value'] > 0),
				'SUBTITLE_FEE' => $system->print_money($row['value'])
				));
	}
	$i++;
}

$template->assign_vars(array(
		'B_SETUP_FEE' => $setup,
		'B_BUYER_FEE' => $buyer_fee,
		'B_ENDAUC_FEE' => $endauc_fee
		));

include 'header.php';
$template->set_filenames(array(
		'body' => 'fees.tpl'
		));
$template->display('body');
include 'footer.php';
?>
