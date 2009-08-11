<?php


$phpbb_hook->register('exit_handler', 'wpu_disable_phpbb_exit');
// prevent default exit from firing, so we can get do an ob callback

function wpu_disable_phpbb_exit() {
	global $innerContent, $phpbb_root_path, $phpEx;
	if (defined('WPU_REVERSE_INTEGRATION')) { 
		$innerContent = ob_get_contents();
		ob_end_clean(); 
		//insert phpBB into a wordpress page
		include ($phpbb_root_path . 'wp-united/integrator.' . $phpEx);
	} elseif (defined('PHPBB_EXIT_DISABLED')) {
		return "";
	}
}

?>