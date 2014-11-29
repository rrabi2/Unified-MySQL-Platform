<?php

$template->assign_vars(array(
		'L_COPY' => empty($system->SETTINGS['copyright']) ? '' : '<p>' . $system->uncleanvars($system->SETTINGS['copyright']) . '</p>',

		'B_VIEW_TERMS' => ($system->SETTINGS['terms'] == 'y'),
		'B_VIEW_PRIVPOL' => ($system->SETTINGS['privacypolicy'] == 'y'),
		'B_VIEW_ABOUTUS' => ($system->SETTINGS['aboutus'] == 'y'),
		'B_FEES' => ($system->SETTINGS['fees'] == 'y')
		));

$template->set_filenames(array(
		'footer' => 'global_footer.tpl'
		));
$template->display('footer');

// if the page has loaded OK you dont need this data anymore :)
unset($_SESSION['SESSION_ERROR']);
?>
