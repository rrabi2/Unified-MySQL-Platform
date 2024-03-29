
include 'common.php';

if (!$user->is_logged_in())
{
	//if your not logged in you shouldn't be here
	header("location: user_login.php");
	exit;
}

$cropdefault = false;
$width = $system->SETTINGS['thumb_show'];
$height = $width / 1.2;
unset($ERR);

function resizeThumbnailImage($thumb_image_name, $image, $width, $height, $start_width, $start_height, $scale)
{
	list($imagewidth, $imageheight, $imageType) = getimagesize($image);
	$imageType = image_type_to_mime_type($imageType);
	// some error checks
	$start_width = ($start_width < 0) ? 0 : $start_width;
	$start_height = ($start_height < 0) ? 0 : $start_height;
	$width = ($imagewidth < $width) ? $imagewidth : $width;
	$height = ($imageheight < $height) ? $imageheight : $height;
	if (($width + $start_width) > $imagewidth)
	{
		$start_width = $imagewidth - $width;
	}
	if (($height + $start_height) > $imageheight)
	{
		$start_height = $imageheight - $height;
	}

	$newImageWidth = ceil($width * $scale);
	$newImageHeight = ceil($height * $scale);
	$newImage = imagecreatetruecolor($newImageWidth, $newImageHeight);
	// make the background white
	$bg = imagecolorallocate($newImage, 0, 0, 0);
	imagefill($newImage, 0, 0, $bg);
	switch ($imageType)
	{
		case 'image/gif':
			$source = imagecreatefromgif ($image);
			break;
		case 'image/pjpeg':
		case 'image/jpeg':
		case 'image/jpg':
			$source = imagecreatefromjpeg($image);
			break;
		case 'image/png':
		case 'image/x-png':
			$source = imagecreatefrompng($image);
			break;
	}
	imagecopyresampled($newImage, $source, 0, 0, $start_width, $start_height, $newImageWidth, $newImageHeight, $width, $height);
	switch ($imageType)
	{
		case 'image/gif':
			imagegif ($newImage, $thumb_image_name);
			break;
		case 'image/pjpeg':
		case 'image/jpeg':
		case 'image/jpg':
			imagejpeg($newImage, $thumb_image_name, 90);
			break;
		case 'image/png':
		case 'image/x-png':
			imagepng($newImage, $thumb_image_name);
			break;
	}
	chmod($thumb_image_name, 0777);
	return $thumb_image_name;
}

// Process delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['img']))
{
	if ($_SESSION['SELL_pict_url_temp'] == $_SESSION['UPLOADED_PICTURES'][intval($_GET['img'])])
	{
		unlink($upload_path . session_id() . '/' . $_SESSION['SELL_pict_url']);
		unset($_SESSION['SELL_pict_url']);
	}
	unlink($upload_path . session_id() . '/' . $_SESSION['UPLOADED_PICTURES'][intval($_GET['img'])]);
	unset($_SESSION['UPLOADED_PICTURES'][intval($_GET['img'])]);
	unset($_SESSION['UPLOADED_PICTURES_SIZE'][intval($_GET['img'])]);
}

if (isset($_GET['action']) && $_GET['action'] == 'makedefault')
{
	$cropdefault = true;
	$image = $_GET['img'];
	$_SESSION['SELL_pict_url_temp'] = $_SESSION['SELL_pict_url'] = $_GET['img'];
}

if (isset($_GET['action']) && $_GET['action'] == 'crop' && !empty($_POST['w']) && $_POST['w'] != 0)
{
	if ($_POST['upload_thumbnail'] == $MSG['616'])
	{
		// Get the new coordinates to crop the image.
		$x1 = intval($_POST['x1']);
		$y1 = intval($_POST['y1']);
		$x2 = intval($_POST['x2']);
		$y2 = intval($_POST['y2']);
		$w = intval($_POST['w']);
		$h = intval($_POST['h']);
		// Scale the image to the thumb_width set above
		$scale = $width / $w;
		$large_image_location = $upload_path . session_id() . '/' . $_GET['img'];
		$thumb_image_location = $upload_path . session_id() . '/thumb-' . $_GET['img'];
		$cropped = resizeThumbnailImage($thumb_image_location, $large_image_location, $w, $h, $x1, $y1, $scale);
		$_SESSION['SELL_pict_url'] = 'thumb-' . $_GET['img'];
		$_SESSION['SELL_pict_url_temp'] = $_GET['img'];
	}
	else
	{
		$_SESSION['SELL_pict_url_temp'] = $_SESSION['SELL_pict_url'] = $_GET['img'];
	}
}

// close window
if (!empty($_POST['creategallery']))
{
	echo '<script type="text/javascript">window.close()</script>';
	exit;
}

// PROCESS UPLOADED FILE
if (isset($_POST['uploadpicture']) && $_POST['uploadpicture'] == $MSG['681'])
{
	if (!empty($_FILES['userfile']['tmp_name']) && $_FILES['userfile']['tmp_name'] != 'none')
	{
		if (!isset($_SESSION['UPLOADED_PICTURES']) || !is_array($_SESSION['UPLOADED_PICTURES'])) $_SESSION['UPLOADED_PICTURES'] = array();
		if (!isset($_SESSION['UPLOADED_PICTURES_SIZE']) || !is_array($_SESSION['UPLOADED_PICTURES_SIZE'])) $_SESSION['UPLOADED_PICTURES_SIZE'] = array();
		$filename = $_FILES['userfile']['name'];
		$nameparts = explode('.', $filename);
		$ext_key = count($nameparts) - 1;
		$file_ext = strtolower($nameparts[$ext_key]);
		$file_types = array('gif', 'jpg', 'jpeg', 'png');

		// clean the name
		unset($nameparts[$ext_key]);
		$newname = implode('_', $nameparts);

		$newname = preg_replace('/[^a-zA-Z0-9_]/', '', $newname);
		$newname .= '.' . $file_ext;

		if ($_FILES['userfile']['size'] > $system->SETTINGS['maxuploadsize'])
		{
			$ERR = $ERR_709 . '&nbsp;' . ($system->SETTINGS['maxuploadsize'] / 1024) . '&nbsp;' . $MSG['672'];
		}
		elseif (!in_array($file_ext, $file_types))
		{
			$ERR = $ERR_710 . ' (' . $file_ext . ')';
		}
		elseif (in_array($newname, $_SESSION['UPLOADED_PICTURES']))
		{
			$ERR = $MSG['2__0054'] . ' (' . $_FILES['userfile']['name'] . ')';
		}
		else
		{
			// Create a TMP directory for this session (if not already created)
			if (!file_exists($upload_path . session_id()))
			{
				umask(0);
				mkdir($upload_path . session_id(), 0777);
				chmod($upload_path . session_id(), 0777); //incase mkdir fails
			}
			// Move uploaded file into TMP directory & rename
			if ($system->move_file($_FILES['userfile']['tmp_name'], $upload_path . session_id() . '/' . $newname))
			{
				// Populate arrays
				array_push($_SESSION['UPLOADED_PICTURES'], $newname);
				$fname = $upload_path . session_id() . '/' . $newname;
				array_push($_SESSION['UPLOADED_PICTURES_SIZE'], filesize($fname));
				if (count($_SESSION['UPLOADED_PICTURES']) == 1)
				{
					$cropdefault = true;
					$image = $newname;
				}
			}
		}
	}
}

if ($cropdefault)
{
	list($imgwidth, $imgheight) = getimagesize($upload_path . session_id() . '/' . $image);
	$swidth = ($imgwidth < 380) ? '' : ' width: 380px;';
	$imgratio = ($imgwidth > 380) ? $imgwidth / 380 : 1;
	$whratio = $imgheight / $imgwidth;
	if ($imgwidth > $imgheight) //landscape
	{
		$ratio = '1.2:1';
		$thumbwh = 'width:' . $width . '; height:' . $height . ';';
		$scaleX = 120;
		$scaleY = 100;
		$startY = 380 * $whratio;
		$startX = $startY * 1.2;
	}
	else //portrait
	{
		$ratio = '1:1.2';
		$thumbwh = 'height:' . $width . '; width:' . $height . ';';
		$scaleX = 100;
		$scaleY = 120;
		$startX = 380 * $whratio;
		$startY = $startX * 1.2;
	}

	$template->assign_vars(array(
			'RATIO' => $ratio,
			'THUMBWH' => $thumbwh,
			'SCALEX' => $scaleX,
			'SCALEY' => $scaleY,
			'IMGRATIO' => $imgratio,
			'SWIDTH' => $swidth,
			'IMGWIDTH' => $imgwidth,
			'IMGHEIGHT' => $imgheight,
			'IMGPATH' => $uploaded_path . session_id() . '/' . $image,
			'STARTX' => $startX,
			'STARTY' => $startY,
			'IMAGE' => $image
			));
}
else
{
	$template->assign_vars(array(
			'MAXIMAGES' => $system->SETTINGS['maxpictures'],
			'ERROR' => (isset($ERR)) ? $ERR : '',

			'B_CANUPLOAD' => (!isset($_SESSION['UPLOADED_PICTURES']) || count($_SESSION['UPLOADED_PICTURES']) < $system->SETTINGS['maxpictures'])
			));
}

// built gallery
foreach ($_SESSION['UPLOADED_PICTURES'] as $k => $v)
{
	$template->assign_block_vars('images', array(
			'IMGNAME' => $v,
			'ID' => $k,
			'DEFAULT' => ($v == $_SESSION['SELL_pict_url_temp']) ? 'selected.gif' : 'unselected.gif',
			'IMAGE' => $uploaded_path . session_id() . '/' . $v
			));
}

if ($system->SETTINGS['fees'] == 'y')
{
	$query = "SELECT value FROM " . $DBPrefix . "fees WHERE type = 'picture_fee'";
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$image_fee = mysql_result($res, 0);
}
else
{
	$image_fee = 0;
}

// get decimals for javascript rounder
$decimals = '';
for ($i = 0; $i < $system->SETTINGS['moneydecimals']; $i++)
{
	$decimals .= 0;
}

$template->assign_vars(array(
		'SITENAME' => $system->SETTINGS['sitename'],
		'THEME' => $system->SETTINGS['theme'],
		'ERROR' => (isset($ERR)) ? $ERR : '',
		'IMAGE_COST' => ($image_fee != 0) ? sprintf($MSG['675'], $image_fee) : '',
		'IMAGE_COST_PLAIN' => ($image_fee != 0) ? $image_fee : 0,
		'PICINFO' => sprintf($MSG['673'], $system->SETTINGS['maxpictures'], $system->SETTINGS['maxuploadsize']),
		'ERRORMSG' => sprintf($MSG['674'], $system->SETTINGS['maxpictures']),
		'MAXPICS' => $system->SETTINGS['maxpictures'],
		'MAXPICSIZE' => $system->SETTINGS['maxuploadsize'],
		'SESSION_ID' => session_id(),
		'UPLOADED' => intval(count($_SESSION['UPLOADED_PICTURES']))
		));
$template->set_filenames(array(
		'body' => 'upldgallery.tpl'
		));
$template->display('body');
?>
