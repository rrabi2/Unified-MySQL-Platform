

include 'common.php';
include $include_path . 'functions_ajax.php';

switch ($_GET['do'])
{
	case 'converter':
		converter_call();
		break;
	case 'uploadaucimages':
		include $main_path . 'inc/plupload/examples/upload.php';
		break;
	case 'getupldtable':
		getupldtable();
		break;
}
?>
