<?php

class EmailBackgroundProcess{

	public static function spawnProcess($newsletter_id, $auth_id){
		shell_exec('env -i php '.ENMDIR.'/lib/cli.backgroundprocess.php ' . escapeshellarg($newsletter_id) . ' ' . escapeshellarg($auth_id) . ' ' . escapeshellarg($_SERVER['HTTP_HOST']) . ' > /dev/null & echo $!');
	}

	public static function killProcess($process_id){
		$return = shell_exec('kill ' . escapeshellarg($process_id));
	}
}