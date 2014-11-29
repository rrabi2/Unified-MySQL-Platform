
include 'common.php';

// Handle banners clickthrough
$query = "SELECT url FROM " . $DBPrefix . "banners WHERE id = " . intval($_GET['banner']);
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);
$URL = mysql_result($res, 0);

// Update clickthrough counter in the database
$query = "UPDATE " . $DBPrefix . "banners set clicks = clicks + 1 WHERE id = " . intval($_GET['banner']);
$system->check_mysql(mysql_query($query), $query, __LINE__, __FILE__);

// Redirect
header('location: ' . $URL);
exit;
?>
