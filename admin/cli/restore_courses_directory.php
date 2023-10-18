<?php

/**
Added 20210414 Derek Maxson so that we can install classes automatically from directory

Usually this will run when a new USB insert is detected by connectbox-pi/ansible/roles/usb-content/files/etc_udev_rules.d_automount_rules 
 */

echo "Installing Classes From $argv[1]\n";

$courses = glob($argv[1] . '/*.mbz');

if (count($courses) == 0) {
	echo "Nothing To Restore in " . $argv[1] . "\n";
	exit (1);
}

foreach ($courses as $course) {
	$command = 'sudo -u www-data php /var/www/moodle/admin/cli/restore_backup.php -f="' . $course . '" -c=1';
	echo "Restoring $course: $command\n";

	$result = `$command`;
	echo $result;
	echo "\n\n===================================================\n";
  
	# Older versions of PHP don't have the function, so we will make one 
	if (!function_exists('str_contains')) {
		function str_contains($haystack, $needle) {
			return $needle !== '' && mb_strpos($haystack, $needle) !== false;
		}
	}    

	# If we have a success message, rename the file so we don't try again
	if (str_contains($result,'Restored course ID')) {
		# Now mark course as restored
		rename ($course,$course . ".RESTORED");
	}
}

exit(0);
?>
