<?php

class EmailBackgroundProcess{
	
	public static function spawnProcess($newsletter_id, $auth_id){
		shell_exec('env -i php '.ENMDIR.'/lib/cli.backgroundprocess.php ' . escapeshellarg($newsletter_id) . ' ' . escapeshellarg($auth_id) . ' > /dev/null & echo $!');
	}
	
	public static function killProcess($process_id){
		shell_exec('kill ' . escapeshellarg($process_id));
	}		
}