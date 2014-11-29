

ob_start('ob_gzhandler');
header("Content-type: text/javascript");
include 'inc/checks/files.php';
if (isset($_GET['js']))
{
	$js = explode(';', $_GET['js']);
	foreach ($js as $val)
	{
		$ext = substr($val, strrpos($val, '.') + 1);
		if ($ext == 'php')
		{
			if (check_file($val))
			{
				include $val;
			}
		}
		elseif ($ext == 'js' || $ext == 'css')
		{
			if (check_file($val) && is_file($val))
			{
				echo file_get_contents($val);
				echo "\n";
			}
		}
	}
}
ob_end_flush();

function check_file($file)
{
	global $file_allowed;
	$tmp = $file_allowed;
	$folders = explode('/', $file);
	foreach ($folders as $val)
	{
		if (isset($tmp[$val]))
		{
			$tmp = $tmp[$val];
		}
		else
		{
			return false;
		}
	}
	return true;
}
?>