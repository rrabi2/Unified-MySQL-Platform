

if (!defined(‘KG’)) exit();

include $include_path . 'maintainance.php';
include $include_path . 'functions_banners.php';
if (basename($_SERVER['PHP_SELF']) != 'error.php')
	include $include_path . 'stats.inc.php';

$jsfiles = 'js/jquery.js;js/jquery.lightbox.js;';
$jsfiles .= (basename($_SERVER['PHP_SELF']) == 'sell.php') ? ';js/calendar.php' : '';

// Get users and auctions counters
$counters = load_counters();

$page_title = (isset($page_title)) ? ' ' . $page_title : '';

$sslurl = $system->SETTINGS['siteurl'];
if ($system->SETTINGS['https'] == 'y')
{
	$sslurl = (!empty($system->SETTINGS['https_url'])) ? $system->SETTINGS['https_url'] : str_replace('http://', 'https://', $system->SETTINGS['siteurl']);
}
// for images/ccs/javascript etc on secure pages
$incurl = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') ? $system->SETTINGS['siteurl'] : $sslurl;

$template->assign_vars(array(
		'DOCDIR' => $DOCDIR, // Set document direction (set in includes/messages.XX.inc.php) ltr/rtl
		'THEME' => $system->SETTINGS['theme'],
		'PAGE_TITLE' => $system->SETTINGS['sitename'] . $page_title,
		'CHARSET' => $CHARSET,
		'DESCRIPTION' => stripslashes($system->SETTINGS['descriptiontag']),
		'KEYWORDS' => stripslashes($system->SETTINGS['keywordstag']),
		'JSFILES' => $jsfiles,
		'LOADCKEDITOR' => (basename($_SERVER['PHP_SELF']) == 'sell.php'),
		'ACTUALDATE' => ActualDate(),
		'LOGO' => ($system->SETTINGS['logo']) ? '<a href="' . $system->SETTINGS['siteurl'] . 'index.php?"><img src="' . $incurl . 'themes/' . $system->SETTINGS['theme'] . '/' . $system->SETTINGS['logo'] . '" border="0" alt="' . $system->SETTINGS['sitename'] . '"></a>' : '&nbsp;',
		'BANNER' => ($system->SETTINGS['banners'] == 1) ? view() : '',
		'HEADERCOUNTER' => $counters,
		'SITEURL' => $system->SETTINGS['siteurl'],
		'SSLURL' => $sslurl,
		'ASSLURL' => ($system->SETTINGS['https'] == 'y' && $system->SETTINGS['usersauth'] == 'y') ? $sslurl : $system->SETTINGS['siteurl'],
		'INCURL' => $incurl,
		'Q' => (isset($q)) ? $q : '',
		'SELECTION_BOX' => file_get_contents($main_path . 'language/' . $language . '/categories_select_box.inc.php'),
		'YOURUSERNAME' => ($user->logged_in) ? $user->user_data['nick'] : '',

		'B_CAN_SELL' => ($user->can_sell || !$user->logged_in),
		'B_LOGGED_IN' => $user->logged_in,
		'B_BOARDS' => ($system->SETTINGS['boards'] == 'y')
		));

$template->set_filenames(array(
		'header' => 'global_header.tpl'
		));
$template->display('header');
?>
