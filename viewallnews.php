

include 'common.php';

$query = "SELECT id, title FROM " . $DBPrefix . "news WHERE suspended = 0 ORDER BY new_date";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

while ($new = mysql_fetch_assoc($res))
{
	$template->assign_block_vars('news', array(
			'TITLE' => stripslashes($new['title']),
			'ID' => $new['id']
			));
}

include 'header.php';
$template->set_filenames(array(
		'body' => 'viewallnews.tpl'
		));
$template->display('body');
include 'footer.php';
?>
