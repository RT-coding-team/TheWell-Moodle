<?php
// This file is part of Moodle - https://moodle.org/
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
 * Sync the course logs with the local API.
 *
 * If you want to use on command line, use `php sync_course_logs.php API_URL API_TOKEN`.
 */
define('CLI_SCRIPT', true);

$url = '';
$token = '';
if ((isset($argv)) && (isset($argv[1]))) {
    $url = $argv[1];
}
if ((isset($argv)) && (isset($argv[2]))) {
    $token = $argv[2];
}
if (empty($url)) {
    echo "\r\nYou must provide a valid API url to post the information to.\r\n";
    exit;
}

set_time_limit(0);

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'datalib.php');
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'course' . DIRECTORY_SEPARATOR . 'lib.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'CurlUtility.php');

$courses = get_courses();
/**
 * This file stores the timestamp when we last pulled the log
 */
$pulledLogFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'logs_last_pulled.txt';
/**
 * @link https://docs.moodle.org/dev/Migrating_log_access_in_reports
 */
$manager = get_log_manager();
$selectedReaders = $manager->get_readers('\core\log\sql_reader');
if ($selectedReaders) {
    $reader = reset($selectedReaders);
} else {
    echo "\r\nNo log readers are available.\r\n";
    exit;
}
$pulledLast = null;
if (file_exists($pulledLogFile)) {
    $pulledLast = file_get_contents($pulledLogFile);
}
$data = [];
foreach ($courses as $course) {
    if (intval($course->id) === 1) {
        // The first course is The Well
        continue;
    }
    $courseDetails = [
        'id'            =>  $course->id,
        'fullname'      =>  $course->fullname,
        'shortname'     =>  $course->shortname,
        'summary'       =>  strip_tags($course->summary),
        'created_on'    =>  $course->timecreated,
        'modified_on'   =>  $course->timemodified
    ];
    $where = 'courseid = ?';
    $params = [$course->id];
    if ($pulledLast) {
        $where .= ' AND timecreated > ?';
        $params[] = $pulledLast;
    }
    $iterator = $reader->get_events_select_iterator($where, $params, 'timecreated ASC', 0, 0);
    $logs = [];
    foreach ($iterator as $item) {
        $extra = $item->get_logextra();
        $origin = ($extra && array_key_exists('origin', $extra) && $extra['origin']) ? $extra['origin'] : '';
        $ip = ($extra && array_key_exists('ip', $extra) && $extra['ip']) ? $extra['ip'] : '';
        $log = [
            'action'        =>  $item->action,
            'component'     =>  $item->component,
            'created_on'    =>  $item->timecreated,
            'crud'          =>  $item->crud,
            'description'   =>  $item->get_description(),
            'event_name'    =>  $item->eventname,
            'name'          =>  $item->get_name(),
            'origin'        =>  $origin,
            'ip_address'    =>  $ip,
            'involved'      =>  []
        ];
        if ($item->userid) {
            $user = $DB->get_record('user', ['id' => $item->userid]);
            if ($user) {
                $log['involved'][] = [
                    'id'            =>  $user->id,
                    'type'          =>  'user',
                    'firstname'     =>  $user->firstname,
                    'lastname'      =>  $user->lastname
                ];
            }
        }
        if (($item->relateduserid) && ($item->relateduserid !== $item->userid)) {
            $user = $DB->get_record('user', ['id' => $item->relateduserid]);
            if ($user) {
                $log['involved'][] = [
                    'id'            =>  $user->id,
                    'type'          =>  'user',
                    'firstname'     =>  $user->firstname,
                    'lastname'      =>  $user->lastname
                ];
            }
        }
        if ($item->objectid) {
            $object = $DB->get_record($item->objecttable, ['id' => $item->objectid]);
            if ($object) {
                $name = '';
                if (property_exists($object, 'description') && $object->description !== '') {
                    $name = $object->description;
                }
                if (property_exists($object, 'shortname') && $object->shortname !== '') {
                    $name = $object->shortname;
                }
                if (property_exists($object, 'name') && $object->name !== '') {
                    $name = $object->name;
                }
                $log['involved'][] = [
                    'id'            =>  $object->id,
                    'type'          =>  $item->objecttable,
                    'name'          =>  $name
                ];
            }
        }
        $logs[] = $log;
    }
    if (!empty($logs)) {
        $data[] = [
            'course'    =>  $courseDetails,
            'logs'      =>  $logs
        ];
    }
}
/**
 * Send everything to the API
 */
$curl = new CurlUtility($url, $token);
if (!empty($data)) {
    $lastSync = ($pulledLast) ? intval($pulledLast) : -1;
    $payload = [
        'data'      =>  $data,
        'last_sync' =>  $lastSync
    ];
    echo "\r\nLOGS:\r\n";
    print_r(json_encode($payload, JSON_NUMERIC_CHECK));

    $curl->makeRequest('/api/lms/stats/logs', 'POST', json_encode($payload), null, true);
    if ($curl->responseCode === 201) {
        echo "\r\nLogs have been successfully sent to the API.\r\n";
        file_put_contents($pulledLogFile, time());
    } else {
        echo "\r\nError! Logs were not sent to the API.\r\n";
    }
}
