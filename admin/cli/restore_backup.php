<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script allows to restore a course from CLI.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . "/backup/util/includes/restore_includes.php");

list($options, $unrecognized) = cli_get_params([
    'file' => '',
    'categoryid' => '',
    'suffix' => '',
    'showdebugging' => false,
    'help' => false,
], [
    'f' => 'file',
    'c' => 'categoryid',
    'x' => 'suffix',
    's' => 'showdebugging',
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !($options['file']) || !($options['categoryid'])) {
    $help = <<<EOL
Restore backup into provided category.

Options:
-f, --file=STRING           Path to the backup file.
-c, --categoryid=INT        ID of the category to restore too.
-x, --suffix=STRING         String to add to the end of the class name for uniqueness
-s, --showdebugging         Show developer level debugging information
-h, --help                  Print out this help.

Example:
\$sudo -u www-data /usr/bin/php admin/cli/restore_backup.php --file=/path/to/backup/file.mbz --categoryid=1\n
EOL;

    echo $help;
    exit(0);
}

if ($options['showdebugging']) {
    set_debugging(DEBUG_DEVELOPER, true);
}

if (!$admin = get_admin()) {
    throw new \moodle_exception('noadmins');
}

if (!file_exists($options['file'])) {
    throw new \moodle_exception('filenotfound');
}

if (!$category = $DB->get_record('course_categories', ['id' => $options['categoryid']], 'id')) {
    throw new \moodle_exception('invalidcategoryid');
}

$backupdir = "restore_" . uniqid();
$path = $CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR . $backupdir;

cli_heading(get_string('extractingbackupfileto', 'backup', $path));
$fp = get_file_packer('application/vnd.moodle.backup');
$fp->extract_to_pathname($options['file'], $path);

cli_heading(get_string('preprocessingbackupfile'));
try {
    list($fullname, $shortname) = restore_dbops::calculate_course_names(0, get_string('restoringcourse', 'backup'),
        get_string('restoringcourseshortname', 'backup'));
    $courseid = restore_dbops::create_new_course($fullname, $shortname, $category->id);

    $rc = new restore_controller($backupdir, $courseid, backup::INTERACTIVE_NO,
        backup::MODE_GENERAL, $admin->id, backup::TARGET_NEW_COURSE);
    $rc->execute_precheck();
    $rc->execute_plan();
    $rc->destroy();
} catch (Exception $e) {

	// Added by Derek Maxson 20210413 -- allows a suffix to be added to the course name
	if ((isset($options['suffix'])) && (!empty($options['suffix']))) {
		echo "Adding Suffix: " . $options['suffix'] . "\n";
		# Older versions of PHP don't have the function, so we will make one 
		if (!function_exists('str_contains')) {
			function str_contains($haystack, $needle) {
				return $needle !== '' && mb_strpos($haystack, $needle) !== false;
			}
		}    

		# Get existing course names and modify them: (1) take out the copy stuff, (2) add the suffix
		$course = $DB->get_record('course', array('id'=>$courseid), 'fullname,shortname', MUST_EXIST);
		if (str_contains($course->fullname,' copy ')) {
			$course->fullname = substr($course->fullname, 0, strpos($course->fullname, " copy "));
			$course->shortname = substr($course->shortname, 0, strpos($course->shortname, "_"));	
		}
		$course->fullname = $course->fullname . ' ' . $options['suffix'];
		$course->shortname = $course->shortname . ' ' . $options['suffix'];

		# Update the database course names via SQL
		$updatesql = "UPDATE mdl_course SET fullname = :fullname, shortname = :shortname WHERE id = :courseid";
		$params = ['fullname' => $course->fullname, 'shortname' => $course->shortname,'courseid' => $courseid];
		$DB->execute($updatesql, $params);
	}

    if (isset($courseid)) {
        /**
         * The course was saved, but then failed to be restored. Let's delete it.
         */
        delete_course($courseid);
    }
    cli_heading(get_string('cleaningtempdata'));
    fulldelete($path);
    throw new \moodle_exception('generalexceptionmessage', 'error', '', $e->getMessage());
}

cli_heading(get_string('restoredcourseid', 'backup', $courseid));
exit(0);
