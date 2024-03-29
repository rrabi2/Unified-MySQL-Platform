
include 'common.php';
include $include_path . 'countries.inc.php';

// check recaptcha is enabled
if ($system->SETTINGS['spam_register'] == 2)
{
	include $main_path . 'inc/captcha/recaptchalib.php';
}
elseif ($system->SETTINGS['spam_register'] == 1)
{
	include $main_path . 'inc/captcha/securimage.php';
}

if ($system->SETTINGS['https'] == 'y' && $_SERVER['HTTPS'] != 'on')
{
	$sslurl = str_replace('http://', 'https://', $system->SETTINGS['siteurl']);
	$sslurl = (!empty($system->SETTINGS['https_url'])) ? $system->SETTINGS['https_url'] : $sslurl;
	header('Location: ' . $sslurl . 'register.php');
	exit;
}

function CheckAge($day, $month, $year) // check if the users > 18
{
	$NOW_year = gmdate('Y');
	$NOW_month = gmdate('m');
	$NOW_day = gmdate('d');

	if (($NOW_year - $year) > 18)
	{
		return 1;
	}
	elseif ((($NOW_year - $year) == 18) && ($NOW_month > $month))
	{
		return 1;
	}
	elseif ((($NOW_year - $year) == 18) && ($NOW_month == $month) && ($NOW_day >= $day))
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

function get_hash()
{
	$string = '0123456789abcdefghijklmnopqrstuvyxz';
	$hash = '';
	for ($i = 0; $i < 5; $i++)
	{
		$rand = rand(0, (34 - $i));
		$hash .= $string[$rand];
		$string = str_replace($string[$rand], '', $string);
	}
	return $hash;
}

function generateSelect($name = '', $options = array())
{
	global $selectsetting;
	$html = '<select name="' . $name . '">';
	foreach ($options as $option => $value)
	{
		if ($selectsetting == $option)
		{
			$html .= '<option value=' . $option . ' selected>' . $value . '</option>';
		}
		else
		{
			$html .= '<option value=' . $option . '>' . $value . '</option>';
		}
	}
	$html .= '</select>';
	return $html;
}

function checkMissing()
{
	global $missing;
	foreach ($missing as $value)
	{
		if ($value)
		{
			return true;
		}
	}
	return false;
}

$first = true;
unset($ERR);

if (empty($_POST['action']))
{
	$action = 'first';
}

$query = "SELECT * FROM " . $DBPrefix . "gateways LIMIT 1";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$gateway_data = mysql_fetch_assoc($res);

// Retrieve users signup settings
$MANDATORY_FIELDS = unserialize($system->SETTINGS['mandatory_fields']);
$DISPLAYED_FIELDS = unserialize($system->SETTINGS['displayed_feilds']);

$spam_html = '';

if ($system->SETTINGS['spam_register'] == 1)
{
	$resp = new Securimage();
	$spam_html = $resp->show_html();
}

// missing check bools
$missing = array();
$missing['birthday'] = $missing['address'] = $missing['city'] = $missing['prov'] = $missing['country'] = $missing['zip'] = $missing['tel'] = $missing['paypal'] = $missing['authnet'] = $missing['worldpay'] = $missing['toocheckout'] = $missing['moneybookers'] = $missing['name'] = $missing['nick'] = $missing['password'] = $missing['repeat_password'] = $missing['email'] = false;
if (isset($_POST['action']) && $_POST['action'] == 'first')
{
	if (!isset($_POST['terms_check']))
	{
		$ERR = $ERR_078;
	}
	if (empty($_POST['TPL_name']))
	{
		$missing['name'] = true;
	}
	if (empty($_POST['TPL_nick']))
	{
		$missing['nick'] = true;
	}
	if (empty($_POST['TPL_password']))
	{
		$missing['password'] = true;
	}
	if (empty($_POST['TPL_repeat_password']))
	{
		$missing['repeat_password'] = true;
	}
	if (empty($_POST['TPL_email']))
	{
		$missing['email'] = true;
	}
	if (empty($_POST['TPL_address']) && $MANDATORY_FIELDS['address'] == 'y')
	{
		$missing['address'] = true;
	}
	if (empty($_POST['TPL_city']) && $MANDATORY_FIELDS['city'] == 'y')
	{
		$missing['city'] = true;
	}
	if (empty($_POST['TPL_prov']) && $MANDATORY_FIELDS['prov'] == 'y')
	{
		$missing['prov'] = true;
	}
	if (empty($_POST['TPL_country']) && $MANDATORY_FIELDS['country'] == 'y')
	{
		$missing['country'] = true;
	}
	if (empty($_POST['TPL_zip']) && $MANDATORY_FIELDS['zip'] == 'y')
	{
		$missing['zip'] = true;
	}
	if (empty($_POST['TPL_phone']) && $MANDATORY_FIELDS['tel'] == 'y')
	{
		$missing['tel'] = true;
	}
	if ((empty($_POST['TPL_day']) || empty($_POST['TPL_month']) || empty($_POST['TPL_year'])) && $MANDATORY_FIELDS['birthdate'] == 'y')
	{
		$missing['birthday'] = true;
	}
	if ($gateway_data['paypal_required'] == 1 && empty($_POST['TPL_pp_email']))
	{
		$missing['paypal'] = true;
	}
	if ($gateway_data['authnet_required'] == 1 && (empty($_POST['TPL_authnet_id']) || empty($_POST['TPL_authnet_pass'])))
	{
		$missing['authnet'] = true;
	}
	if ($gateway_data['moneybookers_required'] == 1 && (empty($_POST['TPL_moneybookers_email']) || !preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i', $_POST['TPL_moneybookers_email'])))
	{
		$missing['moneybookers'] = true;
	}
	if ($gateway_data['toocheckout_required'] == 1 && (empty($_POST['TPL_toocheckout_id'])))
	{
		$missing['toocheckout'] = true;
	}
	if ($gateway_data['worldpay_required'] == 1 && (empty($_POST['TPL_worldpay_id'])))
	{
		$missing['worldpay'] = true;
	}
	if (checkMissing())
	{
		$ERR = $ERR_047;
	}
	if (!isset($ERR))
	{
		$birth_day = (isset($_POST['TPL_day'])) ? $_POST['TPL_day'] : '';
		$birth_month = (isset($_POST['TPL_month'])) ? $_POST['TPL_month'] : '';
		$birth_year = (isset($_POST['TPL_year'])) ? $_POST['TPL_year'] : '';
		$DATE = $birth_year . $birth_month . $birth_day;

		if ($system->SETTINGS['spam_register'] == 2)
		{
			$resp = recaptcha_check_answer($system->SETTINGS['recaptcha_private'], $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
		}

		if ($system->SETTINGS['spam_register'] == 2 && !$resp->is_valid)
		{
			$ERR = $MSG['752'];
		}
		elseif ($system->SETTINGS['spam_register'] == 1 && !$resp->check($_POST['captcha_code']))
		{
			$ERR = $MSG['752'];
		}
		elseif (strlen($_POST['TPL_nick']) < 6)
		{
			$ERR = $ERR_107;
		}
		elseif (strlen ($_POST['TPL_password']) < 6)
		{
			$ERR = $ERR_108;
		}
		elseif ($_POST['TPL_password'] != $_POST['TPL_repeat_password'])
		{
			$ERR = $ERR_109;
		}
		elseif (strlen($_POST['TPL_email']) < 5)
		{
			$ERR = $ERR_110;
		}
		elseif (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i', $_POST['TPL_email']))
		{
			$ERR = $ERR_008;
		}
		elseif (!CheckAge($birth_day, $birth_month, $birth_year) && $MANDATORY_FIELDS['birthdate'] == 'y')
		{
			$ERR = $ERR_113;
		}
		elseif (!empty($birth_month) && !empty($birth_day) && !empty($birth_year) && !checkdate($birth_month, $birth_day, $birth_year))
		{
			$ERR = $ERR_117;
		}
		else
		{
			$sql = "SELECT nick FROM " . $DBPrefix . "users WHERE nick = '" . $system->cleanvars($_POST['TPL_nick']) . "'";
			$res = mysql_query($sql);
			$system->check_mysql($res, $sql, __LINE__, __FILE__);
			if (mysql_num_rows($res) > 0)
			{
				$ERR = $ERR_111; // Selected user already exists
			}
			$query = "SELECT email FROM " . $DBPrefix . "users WHERE email = '" . $system->cleanvars($_POST['TPL_email']) . "'";
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			if (mysql_num_rows($res) > 0)
			{
				$ERR = $ERR_115; // E-mail already used
			}

			if (!isset($ERR))
			{
				$TPL_nick_hidden = $_POST['TPL_nick'];
				$TPL_password_hidden = $_POST['TPL_password'];
				$TPL_name_hidden = $_POST['TPL_name'];
				$TPL_email_hidden = $_POST['TPL_email'];
				$SUSPENDED = ($system->SETTINGS['activationtype'] == 2) ? 0 : 8;
				$SUSPENDED = ($system->SETTINGS['activationtype'] == 0) ? 10 : $SUSPENDED;

				$query = "SELECT value FROM " . $DBPrefix . "fees WHERE type = 'signup_fee'";
				$res = mysql_query($query);
				$system->check_mysql($res, $query, __LINE__, __FILE__);
				$signup_fee = mysql_result($res, 0);
				if ($system->SETTINGS['fee_type'] == 2 && $signup_fee > 0)
				{
					$SUSPENDED = 9;
					$query = "UPDATE " . $DBPrefix . "counters SET inactiveusers = inactiveusers + 1";
					$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
				}
				elseif ($system->SETTINGS['activationtype'] == 1 || $system->SETTINGS['activationtype'] == 0)
				{
					$query = "UPDATE " . $DBPrefix . "counters SET inactiveusers = inactiveusers + 1";
					$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
				}
				else
				{
					$query = "UPDATE " . $DBPrefix . "counters SET users = users + 1";
					$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
				}
				$balance = ($system->SETTINGS['fee_type'] == 2) ? 0 : ($system->SETTINGS['fee_signup_bonus'] - $signup_fee);

				$query = "SELECT id FROM " . $DBPrefix . "groups WHERE auto_join = 1";
				$res = mysql_query($query);
				$system->check_mysql($res, $query, __LINE__, __FILE__);
				$groups = array();
				while ($row = mysql_fetch_assoc($res))
				{
					$groups[] = $row['id'];
				}
				$hash = get_hash();
				$query = "INSERT INTO " . $DBPrefix . "users
						(nick, password, hash, name, address, city, prov, country, zip, phone, nletter, email, reg_date, 
						birthdate, suspended, language, groups, balance, timecorrection, paypal_email, worldpay_id, moneybookers_email, toocheckout_id, authnet_id, authnet_pass)
						VALUES ('" . $system->cleanvars($TPL_nick_hidden) . "',
						'" . md5($MD5_PREFIX . $TPL_password_hidden) . "',
						'" . $hash . "',
						'" . $system->cleanvars($TPL_name_hidden) . "',
						'" . $system->cleanvars($_POST['TPL_address']) . "',
						'" . $system->cleanvars($_POST['TPL_city']) . "',
						'" . $system->cleanvars($_POST['TPL_prov']) . "',
						'" . $system->cleanvars($_POST['TPL_country']) . "',
						'" . $system->cleanvars($_POST['TPL_zip']) . "',
						'" . $system->cleanvars($_POST['TPL_phone']) . "',
						'" . intval($_POST['TPL_nletter']) . "',
						'" . $system->cleanvars($_POST['TPL_email']) . "',
						'" . time() . "',
						'" . ((!empty($DATE)) ? $DATE : 0) . "',
						'" . $SUSPENDED . "',
						'" . $language . "',
						'" . implode(',', $groups) . "',
						'" . $balance . "',
						" . intval($_POST['TPL_timezone']) . ",
						'" . ((isset($_POST['TPL_pp_email'])) ? $system->cleanvars($_POST['TPL_pp_email']) : '') . "',
						'" . ((isset($_POST['TPL_worldpay_id'])) ? $system->cleanvars($_POST['TPL_worldpay_id']) : '') . "',
						'" . ((isset($_POST['TPL_moneybookers_email'])) ? $system->cleanvars($_POST['TPL_moneybookers_email']) : '') . "',
						'" . ((isset($_POST['toocheckout_id'])) ? $system->cleanvars($_POST['toocheckout_id']) : '') . "',
						'" . ((isset($_POST['TPL_authnet_id'])) ? $system->cleanvars($_POST['TPL_authnet_id']) : '') . "',
						'" . ((isset($_POST['TPL_authnet_pass'])) ? $system->cleanvars($_POST['TPL_authnet_pass']) : '') . "')";
				$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);
				$TPL_id_hidden = mysql_insert_id();
				$query = "INSERT INTO " . $DBPrefix . "usersips VALUES
						  (NULL, " . intval($TPL_id_hidden) . ", '" . $_SERVER['REMOTE_ADDR'] . "', 'first','accept')";
				$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);

				$_SESSION['language'] = $language;
				$first = false;

				if (defined('TrackUserIPs'))
				{
					// log registration IP
					$system->log('user', 'Regestered User', $TPL_id_hidden);
				}

				if ($system->SETTINGS['activationtype'] == 0)
				{
					include $include_path . 'email_user_needapproval.php';
					$TPL_message = $MSG['016_a'];
				}
				elseif ($system->SETTINGS['activationtype'] == 1)
				{
					include $include_path . 'email_user_confirmation.php';
					$TPL_message = sprintf($MSG['016'], $TPL_email_hidden, $system->SETTINGS['sitename']);
				}
				else
				{
					$USER = array('name' => $TPL_name_hidden, 'email' => $_POST['TPL_email']);
					include $include_path . 'email_user_approved.php';
					$TPL_message = $MSG['016_b'];
				}

				if ($system->SETTINGS['fee_type'] == 2 && $signup_fee > 0)
				{
					$_SESSION['signup_id'] = $TPL_id_hidden;
					header('location: pay.php?a=3');
					exit;
				}

				$template->assign_vars(array(
						'L_HEADER' => sprintf($MSG['859'], $TPL_name_hidden),
						'L_MESSAGE' => $TPL_message
						));
			}
		}
	}
}

$country = '';

$TIMECORRECTION = array();
for ($i = 12; $i > -13; $i--)
{
	$TIMECORRECTION[$i] = $MSG['TZ_' . $i];
}

$selcountry = isset($_POST['TPL_country']) ? $_POST['TPL_country'] : '';
foreach ($countries as $key => $name)
{
	$country .= '<option value="' . $name . '"';
	if ($name == $selcountry)
	{
		$country .= ' selected';
	}
	elseif ($system->SETTINGS['defaultcountry'] == $name)
	{
		$country .= ' selected';
	}
	$country .= '>' . $name . '</option>' . "\n";
}
$dobclass = ($missing['birthday']) ? ' class="missing"' : '';
$dobmonth = '<select name="TPL_month"' . $dobclass . '>
		<option value="00"></option>
		<option value="01"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '01') ? ' selected' : '') . '>' . $MSG['MON_001E'] . '</option>
		<option value="02"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '02') ? ' selected' : '') . '>' . $MSG['MON_002E'] . '</option>
		<option value="03"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '03') ? ' selected' : '') . '>' . $MSG['MON_003E'] . '</option>
		<option value="04"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '04') ? ' selected' : '') . '>' . $MSG['MON_004E'] . '</option>
		<option value="05"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '05') ? ' selected' : '') . '>' . $MSG['MON_005E'] . '</option>
		<option value="06"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '06') ? ' selected' : '') . '>' . $MSG['MON_006E'] . '</option>
		<option value="07"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '07') ? ' selected' : '') . '>' . $MSG['MON_007E'] . '</option>
		<option value="08"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '08') ? ' selected' : '') . '>' . $MSG['MON_008E'] . '</option>
		<option value="09"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '09') ? ' selected' : '') . '>' . $MSG['MON_009E'] . '</option>
		<option value="10"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '10') ? ' selected' : '') . '>' . $MSG['MON_010E'] . '</option>
		<option value="11"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '11') ? ' selected' : '') . '>' . $MSG['MON_011E'] . '</option>
		<option value="12"' . ((isset($_POST['TPL_month']) && $_POST['TPL_month'] == '12') ? ' selected' : '') . '>' . $MSG['MON_012E'] . '</option>
	</select>';
$dobday = '<select name="TPL_day"' . $dobclass . '>
		<option value=""></option>';
for ($i = 1; $i <= 31; $i++)
{
	$j = (strlen($i) == 1) ? '0' . $i : $i;
	$dobday .= '<option value="' . $j . '"' . ((isset($_POST['TPL_day']) && $_POST['TPL_day'] == $j) ? ' selected' : '') . '>' . $j . '</option>';
}
$dobday .= '</select>';

$selectsetting = (isset($_POST['TPL_timezone'])) ? $_POST['TPL_timezone'] : '';
$time_correction = generateSelect('TPL_timezone', $TIMECORRECTION);

$template->assign_vars(array(
		'ERROR' => (isset($ERR)) ? $ERR : '',
		'L_COUNTRIES' => $country,
		'L_DATEFORMAT' => ($system->SETTINGS['datesformat'] == 'USA') ? $dobmonth . ' ' . $dobday : $dobday . ' ' . $dobmonth,
		'TIMEZONE' => $time_correction,
		'TERMSTEXT' => $system->SETTINGS['termstext'],

		//payment stuff
		'PP_EMAIL' => (isset($_POST['TPL_pp_email'])) ? $_POST['TPL_pp_email'] : '',
		'AN_ID' => (isset($_POST['TPL_authnet_id'])) ? $_POST['TPL_authnet_id'] : '',
		'AN_PASS' => (isset($_POST['TPL_authnet_pass'])) ? $_POST['TPL_authnet_pass'] : '',
		'WP_ID' => (isset($_POST['TPL_worldpay_id'])) ? $_POST['TPL_worldpay_id'] : '',
		'MB_EMAIL' => (isset($_POST['TPL_moneybookers_email'])) ? $_POST['TPL_moneybookers_email'] : '',
		'TC_ID' => (isset($_POST['TPL_toocheckout_id'])) ? $_POST['TPL_toocheckout_id'] : '',

		'B_ADMINAPROVE' => ($system->SETTINGS['activationtype'] == 0),
		'B_NLETTER' => ($system->SETTINGS['newsletter'] == 1),
		'B_FIRST' => $first,
		'B_PAYPAL' => ($gateway_data['paypal_active'] == 1),
		'B_AUTHNET' => ($gateway_data['authnet_active'] == 1),
		'B_WORLDPAY' => ($gateway_data['worldpay_active'] == 1),
		'B_TOOCHECKOUT' => ($gateway_data['toocheckout_active'] == 1),
		'B_MONEYBOOKERS' => ($gateway_data['moneybookers_active'] == 1),

		'CAPTCHATYPE' => $system->SETTINGS['spam_register'],
		'CAPCHA' => ($system->SETTINGS['spam_register'] == 2) ? recaptcha_get_html($system->SETTINGS['recaptcha_public']) : $spam_html,
		'BIRTHDATE' => ($DISPLAYED_FIELDS['birthdate_regshow'] == 'y'),
		'ADDRESS' => ($DISPLAYED_FIELDS['address_regshow'] == 'y'),
		'CITY' => ($DISPLAYED_FIELDS['city_regshow'] == 'y'),
		'PROV' => ($DISPLAYED_FIELDS['prov_regshow'] == 'y'),
		'COUNTRY' => ($DISPLAYED_FIELDS['country_regshow'] == 'y'),
		'ZIP' => ($DISPLAYED_FIELDS['zip_regshow'] == 'y'),
		'TEL' => ($DISPLAYED_FIELDS['tel_regshow'] == 'y'),
		'REQUIRED' => array(
					($MANDATORY_FIELDS['birthdate'] == 'y') ? ' *' : '',
					($MANDATORY_FIELDS['address'] == 'y') ? ' *' : '',
					($MANDATORY_FIELDS['city'] == 'y') ? ' *' : '',
					($MANDATORY_FIELDS['prov'] == 'y') ? ' *' : '',
					($MANDATORY_FIELDS['country'] == 'y') ? ' *' : '',
					($MANDATORY_FIELDS['zip'] == 'y') ? ' *' : '',
					($MANDATORY_FIELDS['tel'] == 'y') ? ' *' : '',
					($gateway_data['paypal_required'] == 1) ? ' *' : '',
					($gateway_data['authnet_required'] == 1) ? ' *' : '',
					($gateway_data['worldpay_required'] == 1) ? ' *' : '',
					($gateway_data['toocheckout_required'] == 1) ? ' *' : '',
					($gateway_data['moneybookers_required'] == 1) ? ' *' : ''
					),
		'MISSING0' => ($missing['name']) ? 1 : 0,
		'MISSING1' => ($missing['nick']) ? 1 : 0,
		'MISSING2' => ($missing['password']) ? 1 : 0,
		'MISSING3' => ($missing['repeat_password']) ? 1 : 0,
		'MISSING4' => ($missing['email']) ? 1 : 0,
		'MISSING5' => ($missing['birthday']) ? 1 : 0,
		'MISSING6' => ($missing['address']) ? 1 : 0,
		'MISSING7' => ($missing['city']) ? 1 : 0,
		'MISSING8' => ($missing['prov']) ? 1 : 0,
		'MISSING9' => ($missing['country']) ? 1 : 0,
		'MISSING10' => ($missing['zip']) ? 1 : 0,
		'MISSING11' => ($missing['tel']) ? 1 : 0,
		'MISSING12' => ($missing['paypal']) ? 1 : 0,
		'MISSING13' => ($missing['authnet']) ? 1 : 0,
		'MISSING14' => ($missing['worldpay']) ? 1 : 0,
		'MISSING15' => ($missing['toocheckout']) ? 1 : 0,
		'MISSING16' => ($missing['moneybookers']) ? 1 : 0,

		'V_YNEWSL' => ((isset($_POST['TPL_nletter']) && $_POST['TPL_nletter'] == 1) || !isset($_POST['TPL_nletter'])) ? 'checked=true' : '',
		'V_NNEWSL' => (isset($_POST['TPL_nletter']) && $_POST['TPL_nletter'] == 2) ? 'checked=true' : '',
		'V_YNAME' => (isset($_POST['TPL_name'])) ? $_POST['TPL_name'] : '',
		'V_UNAME' => (isset($_POST['TPL_nick'])) ? $_POST['TPL_nick'] : '',
		'V_EMAIL' => (isset($_POST['TPL_email'])) ? $_POST['TPL_email'] : '',
		'V_YEAR' => (isset($_POST['TPL_year'])) ? $_POST['TPL_year'] : '',
		'V_ADDRE' => (isset($_POST['TPL_address'])) ? $_POST['TPL_address'] : '',
		'V_CITY' => (isset($_POST['TPL_city'])) ? $_POST['TPL_city'] : '',
		'V_PROV' => (isset($_POST['TPL_prov'])) ? $_POST['TPL_prov'] : '',
		'V_POSTCODE' => (isset($_POST['TPL_zip'])) ? $_POST['TPL_zip'] : '',
		'V_PHONE' => (isset($_POST['TPL_phone'])) ? $_POST['TPL_phone'] : ''
		));

include 'header.php';
$template->set_filenames(array(
		'body' => 'register.tpl'
		));
$template->display('body');
include 'footer.php';
?>
