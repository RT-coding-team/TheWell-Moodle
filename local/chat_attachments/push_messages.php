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
 * Sends messages to Rocketchat.
 *
 * If you want to use on command line, use `php push_messages.php true`
 */
$logToFile = true;
$cliScript = false;
if ((isset($argv)) && (isset($argv[1]))) {
    $cliScript = boolval($argv[1]);
    $logToFile = false;
}
if ((isset($argv)) && (isset($argv[2]))) {
    $logToFile = boolval($argv[2]);
}
if (isset($_GET['logging']) && ($_GET['logging'] === 'display')) {
    $logToFile = false;
}

define('CLI_SCRIPT', $cliScript);

set_time_limit(0);

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once($CFG->libdir . DIRECTORY_SEPARATOR . 'filelib.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Attachment.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'CurlUtility.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'FailedMessagesUtility.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'FileStorageUtility.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'ReportingUtility.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Settings.php');
// Uncomment if you want to disable emailing along with sending chat messages
//$CFG->noemailever = true;

$reporting = new ReportingUtility(dirname(__FILE__), $logToFile);
if ($logToFile) {
    $reporting->clear();
}
$reporting->saveResult('status', 'started');
$reporting->saveStep('script', 'started');
$failedMessages = new FailedMessagesUtility(dirname(__FILE__));
if (!$cliScript) {
    $reporting->printLineBreak = '<br>';
}
$url = Settings::get('server_url', '');
$token = Settings::get('server_authorization', '');
$boxId = shell_exec("cat /sys/class/net/eth0/address | tr ':' '-' | perl -pe 'chomp'");

if ((!$boxId) || ($boxId === '')) {
    $reporting->error('Unable to retrieve the Box ID.', 'set_up');
    $reporting->saveResult('status', 'error');
    $reporting->saveStep('script', 'errored');
    exit;
}
if ($url === '') {
	$reporting->error('No URL provided in the brand.txt file.', 'set_up');
    exit;
}
if ($token === '') {
	$reporting->error('No token provided in the brand.txt file.', 'set_up');
    exit;
}
$reporting->saveResult('box_id', $boxId);
$reporting->saveResult('url', $url);
$reporting->saveResult('token', $token);

// Check for active Internet connection to the world
$output = shell_exec('curl -m 10 -sL -w "%{http_code}\\n" "' . $url . '/chathost/healthcheck?boxid=' . $boxId . '" -o /dev/null');
$output = substr($output, 0, -1);
if ($output != '200') {
	$reporting->info('Chathost: ' . $url . ' is unavailable. Not able to sync. HTTP Code:', $output);
	$reporting->info('Script Exiting!');
	$reporting->saveResult('status', 'error');
	$reporting->saveStep('script', 'errored');
	die();
}
else {
	$reporting->info('Chathost: ' . $url . ' is connected. HTTP Code:', $output);
}

$reporting->info('Sending Requests to: ' . $url . '.', 'script');
$curl = new CurlUtility($url, $token, $boxId);
$fs = get_file_storage();
$systemContext = context_system::instance();
$storage = new FileStorageUtility($DB, $fs, $systemContext->id);

# Test Security
$reporting->info('Checking Secuity Key');
$securityCheck = $curl->makeRequest('/chathost/check', 'GET', [], null, true);
$reporting->info('Chathost: Security Check says ' . $securityCheck);

/**
 * Retrieve the last time we synced
 */
$reporting->saveStep('check_last_sync', 'started');
$reporting->info('Sending GET request to ' . $url . 'messageStatus.', 'check_last_sync');
$lastSync = $curl->makeRequest('/chathost/messageStatus', 'GET', []);
$logMessage = 'The response code for ' . $url . '/chathost/messageStatus was ' . $curl->responseCode . '.';
if ($curl->responseCode !== 200) {
    $reporting->error($logMessage, 'check_last_sync');
    $reporting->saveStep('check_last_sync', 'errored');
    $reporting->saveStep('script', 'errored');
    exit;
}
$reporting->info($logMessage, 'check_last_sync');
$reporting->saveResult('last_time_synced', $lastSync);
$reporting->saveResult('last_time_synced_pretty', date('F j, Y H:i:s', $lastSync));
$reporting->saveStep('check_last_sync', 'completed');

/**
 * Create the course payload to send to the API
 */
$reporting->saveStep('sending_roster', 'started');
$payload = [];
$courses = get_courses();
$studentRole = $DB->get_record('role', ['shortname' =>  'student']);
$teacherRole = $DB->get_record('role', ['shortname' =>  'teacher']);
$editingTeacherRole = $DB->get_record('role', ['shortname' =>  'editingteacher']);
$package = shell_exec("connectboxmanage get package");
$packageStatus = shell_exec("connectboxmanage get packagestatus");
foreach ($courses as $course) {
    $context = context_course::instance($course->id);
    $data = [
        'id'            	=>  intval($course->id),
        'course_name'   	=>  $course->fullname,
        'summary'       	=>  $course->summary,
        'created_on'    	=>  intval($course->timecreated),
        'updated_on'	    =>  intval($course->timemodified),
        'students'      	=>  [],
        'teachers'  	    =>  [],
        'sitename'			=>  Settings::get('server_sitename', ''),
        'siteadmin_name'	=>  Settings::get('server_siteadmin_name', ''),
        'siteadmin_email'	=>  Settings::get('server_siteadmin_email', ''),
        'siteadmin_phone'	=>  Settings::get('server_siteadmin_phone', ''),
        'siteadmin_country'	=>  Settings::get('server_siteadmin_country', ''),
        'package'			=>  $package,
        'packageStatus'     =>  $packageStatus
    ];
    $students = get_role_users($studentRole->id, $context);
    foreach ($students as $student) {
        $data['students'][] = [
            'id'            =>  intval($student->id),
            'username'      =>  $student->username,
            'first_name'    =>  $student->firstname,
            'last_name'     =>  $student->lastname,
            'email'         =>  $student->email,
            'last_accessed' =>  intval($student->lastaccess),
            'language'      =>  $student->lang
        ];
    }
    $teachers = get_role_users($teacherRole->id, $context);
    foreach ($teachers as $teacher) {
        $data['teachers'][] = [
            'id'            =>  intval($teacher->id),
            'username'      =>  $teacher->username,
            'first_name'    =>  $teacher->firstname,
            'last_name'     =>  $teacher->lastname,
            'email'         =>  $teacher->email,
            'last_accessed' =>  intval($teacher->lastaccess),
            'language'      =>  $teacher->lang
        ];
    }
    $editingTeachers = get_role_users($editingTeacherRole->id, $context);
    foreach ($editingTeachers as $teacher) {
        $data['teachers'][] = [
            'id'            =>  intval($teacher->id),
            'username'      =>  $teacher->username,
            'first_name'    =>  $teacher->firstname,
            'last_name'     =>  $teacher->lastname,
            'email'         =>  $teacher->email,
            'last_accessed' =>  intval($teacher->lastaccess),
            'language'      =>  $teacher->lang
        ];
    }
    $payload[] = $data;
}
// Site Administration Data -- Added DM 20210527
echo json_encode($payload, JSON_PRETTY_PRINT);
//

// $reporting->savePayload('course_rooster', $payload);

/**
 * Send the course payload to the API
 */
$reporting->info('Sending POST request to ' . $url . '/chathost/courseRosters.', 'sending_roster');
$curl->makeRequest('/chathost/courseRosters', 'POST', json_encode($payload) , null, true);
$logMessage = 'The response code for ' . $url . '/chathost/courseRosters was ' . $curl->responseCode . '.';
if ($curl->responseCode === 200) {
    $reporting->info($logMessage, 'sending_roster');
    $reporting->saveStep('sending_roster', 'completed');
} else {
    $reporting->error($logMessage, 'sending_roster');
    $reporting->saveStep('sending_roster', 'errored');
}

/**
 * Gather up the messages to send to the API
 */
$reporting->saveStep('sending_messages', 'started');
$payload = [];
$attachments = [];
$query = 'SELECT m.id, m.conversationid, m.subject, m.fullmessage, m.fullmessagehtml, m.timecreated, s.id as sender_id, ' .
        's.username as sender_username, s.email as sender_email, r.id as recipient_id, r.username as recipient_username, ' .
        'r.email as recipient_email FROM {messages} AS m INNER JOIN {message_conversation_members} AS mcm ON m.conversationid=mcm.conversationid ' .
        'INNER JOIN {user} AS r ON mcm.userid = r.id INNER JOIN {user} AS s ON m.useridfrom = s.id ' .
        'WHERE m.useridfrom <> mcm.userid AND m.from_rocketchat = 0 AND  m.timecreated > ? ORDER BY m.timecreated ASC';
$chats = $DB->get_records_sql($query, [$lastSync]);
foreach ($chats as $chat) {
    $message = htmlspecialchars_decode($chat->fullmessagehtml);
    if (strlen($message) == 0) { $message = $chat->fullmessage; }  // Added by DM 20210524 to catch messages that display as fullmessage
    $attachment = null;
    if (Attachment::isAttachment($message)) {
        $attachment = new Attachment($message);
        $attachments[] = $attachment;
    }
    $data = [
        'id'                =>  intval($chat->id),
        'conversation_id'   =>  intval($chat->conversationid),
        'subject'           =>  $chat->subject,
        'message'           =>  $message,
        'sender'            =>  [
            'id'        =>  intval($chat->sender_id),
            'username'  =>  $chat->sender_username,
            'email'     =>  $chat->sender_email
        ],
        'recipient'            =>  [
            'id'        =>  intval($chat->recipient_id),
            'username'  =>  $chat->recipient_username,
            'email'     =>  $chat->recipient_email
        ],
        'attachment'    =>  null,
        'created_on'    =>  intval($chat->timecreated)
    ];
    if ($attachment) {
        $data['attachment'] = $attachment->toArray();
    }
    $payload[] = $data;
}
// $reporting->savePayload('messages_to_send', $payload);

/**
 * Send each attachment to the API
 *
 */
$reporting->info('Sending attachments.', 'sending_attachments');
$reporting->saveStep('sending_attachments', 'started');
$reporting->startProgress('Sending attachments', count($attachments));
foreach ($attachments as $attachment) {
    $filepath = $storage->retrieve($attachment->id, $attachment->filepath, $attachment->filename);
    if ((!$filepath) || (!file_exists($filepath))) {
        continue;
    }
    //Check if file exists.  If returns 404, then send file
    $curl->makeRequest('/chathost/attachments/' . $attachment->id . '/exists', 'GET', []);
    if ($curl->responseCode === 404) {
        $response = $curl->makeRequest('/chathost/attachments', 'POST', $attachment->toArray(), $filepath);
        if ($curl->responseCode === 200) {
            $reporting->reportProgressSuccess();
        } else {
            $reporting->reportProgressError();
        }
        $reporting->info('Sent attachment #' . $attachment->id . 'with status ' . $curl->responseCode . '.', 'send_attachments');
    } else {
        $reporting->info('Attachment #' . $attachment->id . ' previously sent.', 'send_attachments');
        $reporting->reportProgressSuccess();
    }
    unlink($filepath);
}
$reporting->saveResult('total_attachments_sent', $reporting->getProgressSuccess());
$reporting->saveResult('total_attachments_sent_failed', $reporting->getProgressError());
if ($reporting->getProgressError() > 0) {
    $reporting->saveStep('sending_attachments', 'errored');
} else {
    $reporting->saveStep('sending_attachments', 'completed');
}
$reporting->stopProgress();

/**
 * Send the message payload to the API
 */
$reporting->info('Sending POST request to ' . $url . 'messages.', 'sending_messages');
$curl->makeRequest('/chathost/messages', 'POST', json_encode($payload), null, true);
$logMessage = 'The response code for ' . $url . '/chathost/messages was ' . $curl->responseCode . '.';
if ($curl->responseCode === 200) {
    $reporting->saveResult('total_messages_sent', count($chats));
    $reporting->info($logMessage, 'sending_messages');
    $reporting->saveStep('sending_messages', 'completed');
} else {
    $reporting->saveResult('total_messages_sent', 0);
    $reporting->error($logMessage, 'sending_messages');
    $reporting->saveStep('sending_messages', 'errored');
}

/**
 * Now request new messages from the API
 */
$reporting->saveStep('receiving_messages', 'started');
$reporting->info('Sleeping To Allow Chathost To Compile Messages.');
sleep (5);
$reporting->info('Retrieving new messages.', 'receiving_messages');
$reporting->info('Sending GET request to ' . $url . 'messages/' . $lastSync . '.', 'receiving_messages');
$response = $curl->makeRequest('/chathost/messages/' . $lastSync, 'GET', [], null, true);
$logMessage = 'The response code for ' . $url . '/chathost/messages/' . $lastSync . ' was ' . $curl->responseCode . '.';
if ($curl->responseCode === 200) {
    $reporting->info($logMessage, 'receiving_messages');
    $newMessages = json_decode($response);
} else {
    $reporting->error($logMessage, 'receiving_messages');
    $reporting->saveStep('receiving_messages', 'errored');
    $newMessages = [];
}
//$reporting->savePayload('messages_received', $newMessages);
$reporting->saveResult('total_messages_received', count($newMessages));
if (($curl->responseCode === 200) && (count($newMessages) === 0)) {
    $reporting->info('There are no new messages.', 'receiving_messages');
    $reporting->saveStep('receiving_messages', 'completed');
    $reporting->saveResult('total_messages_received_completed', 0);
    $reporting->saveResult('total_messages_received_failed', 0);
} else if ($curl->responseCode === 200) {
    $reporting->info('Total Messages Received: ' . number_format(count($newMessages)) . '.', 'receiving_messages');

    /**
     * For each message, retrieve the attachment, save it to moodle, and save the new message.
     */
    $reporting->startProgress('Saving retrieved messages & attachments', count($newMessages));
    foreach ($newMessages as $message) {
        $content = $message->message;
// Take Usernames and Convert To UserIDs and ConversationIDs -- DM 20210524
		// Match usernames to userids
		$query = 'SELECT 0 as id,sender.id as senderId, sender.username as senderUsername, recipient.id as recipientId, recipient.username as recipientUsername
		 	FROM {user} sender CROSS JOIN {user} recipient
		 	where sender.username = ? AND recipient.username=?';
		$userinfo = $DB->get_records_sql($query, [$message->sender->username,$message->recipient->username]);
		$message->sender->id = $userinfo[0]->senderid;
		$message->recipient->id = $userinfo[0]->recipientid;

		// Now get the conversationid.  If exists, this function returns the existing conversation.  Never duplicates them!
		$conversation = \core_message\api::create_conversation(
			\core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
			[
				$message->sender->id,
				$message->recipient->id
			]
		);
		if ($conversation->id > 0) {
			$message->conversation_id = $conversation->id;
		}
		else {
			die();
		}
// End modifications
        if (Attachment::isAttachment($content)) {
            $attachment = new Attachment($content);
            /**
             * Download and save the attachment
             */
            if ($attachment->id <= 0) {
                // cannot get the attachment.  Move along.
                $reporting->reportProgressError();
                continue;
            }

            $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $attachment->filename;
			$downloaded = $curl->downloadFile($attachment->filepath, $tempPath);
            if (!$downloaded) {
                $reporting->error('Unable to download attachment ' . $attachment->filename . '.', 'receiving_messages');
                $reporting->reportProgressError();
                $failedMessages->add(
                    $message->_id,
                    $message->sender->id,
                    $message->conversation_id,
                    $message->message
                );
                continue;
            }
            $reporting->info('Received attachment #' . $attachment->filename . '.', 'receiving_messages');
            $attachment->id = $storage->store($attachment->filename, $tempPath);
			$attachment->filepath = '/';
            $content = $attachment->toString();
            unlink($tempPath);
        }
        // Location in messages/classes/api.php
        $message = \core_message\api::send_message_to_conversation(
            $message->sender->id,
            $message->conversation_id,
            htmlspecialchars($content),
            FORMAT_HTML
        );
        $DB->execute('UPDATE {messages} SET from_rocketchat = 1 WHERE id = ?', [$message->id]);
        $reporting->reportProgressSuccess();
    }
    $reporting->saveResult('total_messages_received_completed', $reporting->getProgressSuccess());
    $reporting->saveResult('total_messages_received_failed', $reporting->getProgressError());
    if ($reporting->getProgressError() > 0) {
        $reporting->saveStep('receiving_messages', 'errored');
    } else {
        $reporting->saveStep('receiving_messages', 'completed');
    }
    $reporting->stopProgress();
}


/**
 * Send System Logs
 * Added by Derek Maxson 20210616 / Revised 20220427
 */
$reporting->info('Preparing To Send Logs', 'sending_logs');
$output = shell_exec("connectboxmanage get syncweblog");
$logs = explode("\n", $output);
foreach ($logs as $log) {
	$logs[] = [
		'log' => $log
	];
}
$curl->makeRequest('/chathost/logs/content', 'POST', json_encode($logs) , null, true);
echo $curl->responseCode;
$reporting->saveStep('sending_logs', 'completed');

/**
 * Retrieve Settings
 * Added by Derek Maxson 20210616
 */
$reporting->info('Preparing To Get Settings', 'get_settings');
$reporting->info('Sending GET request to ' . $url . 'settings.', 'get_settings');
$response = $curl->makeRequest('/chathost/settings', 'GET', [], null, true);
$logMessage = 'The response code for ' . $url . '/chathost/settings was ' . $curl->responseCode . '.';
if ($curl->responseCode === 200) {
    $reporting->info($logMessage, 'get_settings');
    $settings = json_decode($response);
} else {
    $reporting->error($logMessage, 'get_settings');
    $reporting->saveStep('get_settings', 'errored');
    $reporting->saveStep('script', 'errored');
    $settings = [];
}
// Iterate through each setting and set, then delete the setting from the server
$reporting->saveResult('get_settings', json_encode($settings, JSON_PRETTY_PRINT));
foreach ($settings as $setting) {
	$reporting->info('Executing Setting Change: ' . $setting->key . '=' . $setting->value, 'get_settings');
	if ($setting->key === 'moodle-security-key') {
		shell_exec("sudo connectboxmanage set securitykey $setting->value");
		$reporting->info('DONE: Setting Change via Moodle: ' . $setting->key . '=' . $setting->value, 'get_settings');
	}
	else {
		$output = shell_exec("sudo connectboxmanage set $setting->key $setting->value");
		$reporting->info($output);
		$reporting->info('DONE: Setting Change: ' . $setting->key . '=' . $setting->value, 'get_settings');
	}
	$curl->makeRequest('/chathost/settings/' . $setting->deleteId, 'DELETE', []);
	$reporting->info('DONE: Delete Setting Change: ' . $setting->key . '=' . $setting->value, 'get_settings');
}

// Kick Off lazyLoader to sync any content subscriptions
$reporting->info('Launch connectboxmanage do openwellrefresh in background to sync subscribed content', 'get_settings');
shell_exec("sudo connectboxmanage do openwellrefresh >/dev/null 2>/dev/null &");

/**
 * Script finished
 */
$reporting->info('Script Complete!');
$reporting->saveResult('status', 'completed');
$reporting->saveStep('script', 'completed');

/**
 * Send the report to the API
 */
//$reporting->info('Sending Sync Log');
//$showlogs = $reporting->read();
// todo this doesn't seem to be pulling the log data to send so I'm disabling for now.
//echo $showlogs;
//$curl->makeRequest('/chathost/logs/sync', 'POST', json_encode($logs), null, true);
//echo $curl->responseCode;
