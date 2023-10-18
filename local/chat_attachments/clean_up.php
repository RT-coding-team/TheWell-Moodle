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
 * This script requires ffmpeg to be installed on the server.  It compresses attachements.
 */
define('CLI_SCRIPT', true);
set_time_limit(0);

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once($CFG->libdir . DIRECTORY_SEPARATOR . 'filelib.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Attachment.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'FileStorageUtility.php');
$numOfDays = 90;
$unavailableMessage = '<i class="fa fa-exclamation-triangle" style="font-size: 60px;"></i>';
if ((isset($argv)) && (isset($argv[1]))) {
    $numOfDays = intval($argv[1]);
}
if ($numOfDays <= 1) {
    echo "Please pick a number of days greater than 1.\r\n";
    exit;
}

echo "Cleaning up files older than " . $numOfDays . " days old.\r\n";

$threshold = time() - ($numOfDays * (24 * (60 * 60)));
$fs = get_file_storage();
$systemContext = context_system::instance();
$storage = new FileStorageUtility($DB, $fs, $systemContext->id);
/**
 * Find all messages older than the $threshold.
 */
$query = 'SELECT m.id, m.conversationid, m.subject, m.fullmessagehtml, m.timecreated, s.id as sender_id, ' .
     's.username as sender_username, s.email as sender_email, r.id as recipient_id, r.username as recipient_username, ' .
     'r.email as recipient_email FROM {messages} AS m INNER JOIN {message_conversation_members} AS mcm ON m.conversationid=mcm.conversationid ' .
     'INNER JOIN {user} AS s ON mcm.userid = s.id INNER JOIN {user} AS r ON m.useridfrom = r.id ' .
     'WHERE m.useridfrom <> mcm.userid AND  m.timecreated < ? ORDER BY m.timecreated ASC';
$chats = $DB->get_records_sql($query, [$threshold]);
$total = 0;
foreach ($chats as $chat) {
    $message = htmlspecialchars_decode($chat->fullmessagehtml);
    if (!Attachment::isAttachment($message)) {
        /**
         * Not an attachment so ignore it.
         */
        continue;
    }
    $attachment = new Attachment($message);
    /**
     * Delete the file associated with these messages.
     */
    $storage->delete($attachment->id);
    /**
     * Replace the message content with a missing icon.
     */
    $DB->execute(
        'UPDATE {messages} SET smallmessage = ?, fullmessage = ?, fullmessagehtml = ? WHERE id = ?',
        [$unavailableMessage, $unavailableMessage, $unavailableMessage, $chat->id]
    );
    $total++;
}
echo "Total Purged: " . $total . "\r\n";
echo "Clean Up Completed!\r\n";
