
include 'common.php';
include $main_path . 'inc/captcha/securimage.php';

$img = new Securimage();

$img->show();
?>