<?php

include 'common.php';
include $main_path . 'language/' . $language . '/categories.inc.php';

// Run cron according to SETTINGS
if ($system->SETTINGS['cron'] == 2)
{
	include_once 'cron.php';
}

if ($system->SETTINGS['loginbox'] == 1 && $system->SETTINGS['https'] == 'y' && $_SERVER['HTTPS'] != 'on')
{
	$sslurl = str_replace('http://', 'https://', $system->SETTINGS['siteurl']);
	$sslurl = (!empty($system->SETTINGS['https_url'])) ? $system->SETTINGS['https_url'] : $sslurl;
	header('Location: ' . $sslurl . 'index.php');
	exit;
}

$NOW = time();

function ShowFlags()
{
	global $system, $LANGUAGES;
	$counter = 0;
	$flags = '';
	foreach ($LANGUAGES as $lang => $value)
	{
		if ($counter > 3)
		{
			$flags .= '<br>';
			$counter = 0;
		}
		$flags .= '<a href="?lan=' . $lang . '"><img vspace="2" hspace="2" src="' . $system->SETTINGS['siteurl'] . 'inc/flags/' . $lang . '.gif" border="0" alt="' . $lang . '"></a>';
		$counter++;
	}
	return $flags;
}

// prepare categories list for templates/template
// Prepare categories sorting
if ($system->SETTINGS['catsorting'] == 'alpha')
{
	$catsorting = ' ORDER BY cat_name ASC';
}
else
{
	$catsorting = ' ORDER BY sub_counter DESC';
}

$query = "SELECT cat_id FROM " . $DBPrefix . "categories WHERE parent_id = -1";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$query = "SELECT * FROM " . $DBPrefix . "categories
		  WHERE parent_id = " . mysql_result($res, 0) . "
		  " . $catsorting . "
		  LIMIT " . $system->SETTINGS['catstoshow'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

while ($row = mysql_fetch_assoc($res))
{
	$template->assign_block_vars('cat_list', array(
			'CATAUCNUM' => ($row['sub_counter'] != 0) ? '(' . $row['sub_counter'] . ')' : '',
			'ID' => $row['cat_id'],
			'IMAGE' => (!empty($row['cat_image'])) ? '<img src="' . $row['cat_image'] . '" border=0>' : '',
			'COLOUR' => (empty($row['cat_colour'])) ? '#FFFFFF' : $row['cat_colour'],
			'NAME' => $category_names[$row['cat_id']]
			));
}

// get featured items
$query = "SELECT id, title, current_bid, pict_url, ends, num_bids, minimum_bid, bn_only, buy_now
        FROM " . $DBPrefix . "auctions
        WHERE closed = 0 AND suspended = 0 AND starts <= " . $NOW . "
		AND featured = 'y'
        ORDER BY RAND() DESC LIMIT 12";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
while($row = mysql_fetch_assoc($res))
{
	$ends = $row['ends'];
	$difference = $ends - time();
	if ($difference > 0)
	{
		$ends_string = FormatTimeLeft($difference);
	}
	else
	{
		$ends_string = $MSG['911'];
	}
	$high_bid = ($row['num_bids'] == 0) ? $row['minimum_bid'] : $row['current_bid'];
	$high_bid = ($row['bn_only'] == 'y') ? $row['buy_now'] : $high_bid;
	$template->assign_block_vars('featured', array(
			'ENDS' => $ends_string,
			'ID' => $row['id'],
			'BID' => $system->print_money($high_bid),
			'IMAGE' => (!empty($row['pict_url'])) ? 'getthumb.php?w=' . $system->SETTINGS['thumb_show'] . '&fromfile=' . $uploaded_path . $row['id'] . '/' . $row['pict_url'] : 'images/email_alerts/default_item_img.jpg',
			'TITLE' => $row['title']
			));
}

// get last created auctions
$query = "SELECT id, title, starts from " . $DBPrefix . "auctions
		 WHERE closed = 0 AND suspended = 0
		 AND starts <= " . $NOW . "
		 ORDER BY starts DESC
		 LIMIT " . $system->SETTINGS['lastitemsnumber'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$i = 0;
while ($row = mysql_fetch_assoc($res))
{
	$template->assign_block_vars('auc_last', array(
			'BGCOLOUR' => (!($i % 2)) ? '' : 'class="alt-row"',
			'DATE' => ArrangeDateNoCorrection($row['starts'] + $system->tdiff),
			'ID' => $row['id'],
			'TITLE' => $row['title']
			));
	$i++;
}

$auc_last = ($i > 0) ? true : false;
// get ending soon auctions
$query = "SELECT ends, id, title FROM " . $DBPrefix . "auctions
		 WHERE closed = 0 AND suspended = 0 AND starts <= " . $NOW . "
		 ORDER BY ends LIMIT " . $system->SETTINGS['endingsoonnumber'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$i = 0;
while ($row = mysql_fetch_assoc($res))
{
	$difference = $row['ends'] - time();
	if ($difference > 0)
	{
		$ends_string = FormatTimeLeft($difference);
	}
	else
	{
		$ends_string = $MSG['911'];
	}
	$template->assign_block_vars('end_soon', array(
			'BGCOLOUR' => (!($i % 2)) ? '' : 'class="alt-row"',
			'DATE' => $ends_string,
			'ID' => $row['id'],
			'TITLE' => $row['title']
			));
	$i++;
}

$end_soon = ($i > 0) ? true : false;
// get hot items
$query = "SELECT a.id, a.title, a.current_bid, a.pict_url, a.ends, a.num_bids, a.minimum_bid 
        FROM " . $DBPrefix . "auctions a 
        LEFT JOIN " . $DBPrefix . "auccounter c ON (a.id = c.auction_id) 
        WHERE closed = 0 AND suspended = 0 AND starts <= " . $NOW . " 
        ORDER BY c.counter DESC LIMIT " . $system->SETTINGS['hotitemsnumber'];
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$i = 0;
while ($row = mysql_fetch_assoc($res))
{
	$i++;
	$ends = $row['ends'];
    $difference = $ends - time();
    if ($difference > 0)
	{
        $ends_string = FormatTimeLeft($difference); 
    }
	else
	{
        $ends_string = $MSG['911'];
    }
    $high_bid = ($row['num_bids'] == 0) ? $row['minimum_bid'] : $row['current_bid'];
    $template->assign_block_vars('hotitems', array(
            'ENDS' => $ends_string,
            'ID' => $row['id'],
            'BID' => $system->print_money($high_bid),
            'IMAGE' => (!empty($row['pict_url'])) ? 'getthumb.php?w=' . $system->SETTINGS['thumb_show'] . '&fromfile=' . $uploaded_path . $row['id'] . '/' . $row['pict_url'] : 'images/email_alerts/default_item_img.jpg',
            'TITLE' => $row['title']
            ));
}
$hot_items = ($i > 0) ? true : false;

// Build list of help topics
$query = "SELECT id, category FROM " . $DBPrefix . "faqscat_translated WHERE lang = '" . $language . "' ORDER BY category ASC";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$i = 0;
while ($faqscat = mysql_fetch_assoc($res))
{
	$template->assign_block_vars('helpbox', array(
			'ID' => $faqscat['id'],
			'TITLE' => $faqscat['category']
			));
	$i++;
}

$helpbox = ($i > 0) ? true : false;
// Build news list
if ($system->SETTINGS['newsbox'] == 1)
{
	$query = "SELECT n.title As t, n.new_date, t.* FROM " . $DBPrefix . "news n
			LEFT JOIN " . $DBPrefix . "news_translated t ON (t.id = n.id)
			WHERE t.lang = '" . $language . "' AND n.suspended = 0
			ORDER BY new_date DESC, id DESC LIMIT " . $system->SETTINGS['newstoshow'];
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	while ($new = mysql_fetch_assoc($res))
	{
		$template->assign_block_vars('newsbox', array(
				'ID' => $new['id'],
				'DATE' => FormatDate($new['new_date']),
				'TITLE' => (!empty($new['title'])) ? $new['title'] : $new['t']
				));
	}
}

$template->assign_vars(array(
		'FLAGS' => ShowFlags(),

		'B_AUC_LAST' => $auc_last,
		'B_HOT_ITEMS' => $hot_items,
		'B_AUC_ENDSOON' => $end_soon,
		'B_HELPBOX' => ($helpbox && $system->SETTINGS['helpbox'] == 1),
		'B_MULT_LANGS' => (count($LANGUAGES) > 1),
		'B_LOGIN_BOX' => ($system->SETTINGS['loginbox'] == 1),
		'B_NEWS_BOX' => ($system->SETTINGS['newsbox'] == 1)
		));

include 'header.php';
$template->set_filenames(array(
		'body' => 'home.tpl'
		));
$template->display('body');
include 'footer.php';

unset($_SESSION['loginerror']);
?>
