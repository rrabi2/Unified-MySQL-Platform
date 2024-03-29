

include 'common.php';
include $main_path . 'language/' . $language . '/categories.inc.php';

$NOW = time();
$p24h = time() + (24 * 60 * 60);
$m24h = time() - (24 * 60 * 60);
$catscontrol = new MPTTcategories();

$user_id = (isset($_REQUEST['user_id'])) ? intval($_REQUEST['user_id']) : 0;
$limit = ' LIMIT ' . $system->SETTINGS['perpage'];

$feed = (isset($_GET['feed'])) ? intval($_GET['feed']) : '';

switch ($feed)
{
	case 1: 
		$RSStitle = $MSG['924']; // items listed in the last 24 hours
		$postdate = 'starts';
		$sort = 'DESC';
		$subquery = 'a.starts <= ' . $NOW . ' AND a.starts > ' . $m24h;
		break;

	case 2: 
		$RSStitle = $MSG['925']; // items closing in 24 hours or less
		$postdate = 'ends';
		$sort = 'ASC';
		$subquery = 'a.starts <= ' . $NOW . ' AND a.ends <= ' . $p24h;
		break;

	case 3: 
		$RSStitle = $MSG['926']; // items over 300.00
		$postdate = 'ends';
		$sort = 'ASC';
		$subquery = 'a.starts <= ' . $NOW . ' AND (a.current_bid >= 300 OR a.minimum_bid >= 300 OR a.buy_now >= 300)';
		break;

	case 4: 
		$RSStitle = $MSG['927']; // items over 1000.00
		$postdate = 'ends';
		$sort = 'ASC';
		$subquery = 'a.starts <= ' . $NOW . ' AND (a.current_bid >= 1000 OR a.minimum_bid >= 1000 OR a.buy_now >= 1000)';
		break;

	case 5: 
		$RSStitle = $MSG['928'];
		$postdate = 'starts';
		$sort = 'DESC';
		$subquery = 'a.starts <= ' . $NOW . ' AND (a.current_bid <= 10 OR a.buy_now <= 10)';
		break;

	case 6: 
		$RSStitle = $MSG['929']; // items with 10 or more bids
		$postdate = 'starts';
		$sort = 'DESC';
		$subquery = 'a.starts <= ' . $NOW . ' AND a.num_bids >= 10';
		break;

	case 7: 
		$RSStitle = $MSG['930']; // items with 25 or more bids
		$postdate = 'starts';
		$sort = 'DESC';
		$subquery = 'a.starts <= ' . $NOW . ' AND a.num_bids >= 25';
		break;

	case 8: 
		$RSStitle = $MSG['931']; // item with a Buy Now
		$postdate = 'starts';
		$sort = 'DESC';
		$subquery = 'a.starts <= ' . $NOW . ' AND a.buy_now > 0';
		break;

	default:
		$postdate = 'starts';
		if ($user_id > 0)
		{
			$query = "SELECT nick FROM " . $DBPrefix . "users WHERE id = " . $user_id;
			$res = mysql_query($query);
			$system->check_mysql($res, $query, __LINE__, __FILE__);
			$username = mysql_result($res, 0, 'nick');
			$sort = 'DESC';
			$subquery = 'a.starts <= ' . $NOW . ' AND a.ends > ' . $NOW . ' AND a.user = ' . $user_id;
			$RSStitle = sprintf($MSG['932'], $username);
		}
		else
		{
			$RSStitle = $MSG['924'];
			$sort = 'DESC';
			$subquery = 'a.starts <= ' . $NOW . ' AND a.starts > ' . $m24h;
		}
		break;
}

$query = "SELECT a.*, u.nick from " . $DBPrefix . "auctions a
		LEFT JOIN " . $DBPrefix . "users u ON (u.id = a.user)
		WHERE a.closed = 0 AND a.suspended = 0 AND " . $subquery . "
		ORDER BY " . $postdate . " " . $sort . " " . $limit;
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
while ($auction_data = mysql_fetch_assoc($res))
{
	$query = "SELECT left_id, right_id, level FROM " . $DBPrefix . "categories WHERE cat_id = " . $auction_data['category'];
	$res_ = mysql_query($query);
	$system->check_mysql($res_, $query, __LINE__, __FILE__);
	$parent_node = mysql_fetch_assoc($res_);

	$cat_value = '';
	$crumbs = $catscontrol->get_bread_crumbs($parent_node['left_id'], $parent_node['right_id']);
	for ($i = 0; $i < count($crumbs); $i++)
	{
		if ($crumbs[$i]['cat_id'] > 0)
		{
			if ($i > 0)
			{
				$cat_value .= ' / ';
			}
			$cat_value .= '<a href="' . $system->SETTINGS['siteurl'] . 'browse.php?id=' . $crumbs[$i]['cat_id'] . '">' . $category_names[$crumbs[$i]['cat_id']] . '</a>';
		}
	}

	$template->assign_block_vars('rss', array(
			'PRICE' => str_replace(array('<b>', '</b>'), '', $system->print_money(($auction_data['num_bids'] == 0) ? $auction_data['minimum_bid'] : $auction_data['current_bid'])),
			'TITLE' => $system->uncleanvars($auction_data['title']),
			'URL' => $system->SETTINGS['siteurl'] . 'item.php?id=' . $auction_data['id'],
			'DESC' => $auction_data['description'],
			'USER' => $auction_data['nick'],
			'POSTED' => gmdate('Y-m-d\TH:i:s-00:00', $auction_data['starts']),
			//'POSTED' => gmdate('D, j M Y H:i:s \G\M\T', $auction_data['starts']),
			'CAT' => $cat_value
			));
}

$template->assign_vars(array(
		'XML' => '<?xml version="1.0" encoding="' . $CHARSET . '"?>', //as the template parser doesnt like <? tags
		'PAGE_TITLE' => $system->SETTINGS['sitename'],
		'SITEURL' => $system->SETTINGS['siteurl'],
		'RSSTITLE' => $RSStitle
		));

$template->set_filenames(array(
		'body' => 'rss.tpl'
		));
$template->display('body');
?>
