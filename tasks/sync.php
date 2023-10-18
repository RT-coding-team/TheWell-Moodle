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
 * Triggers the sync command in local/chat_attachments without keeping the user waiting.
 */
$rootDir = dirname(dirname(__FILE__));
$script = $rootDir . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'chat_attachments' . DIRECTORY_SEPARATOR . 'push_messages.php';
if (file_exists($script)) {
    exec('php ' . $script . ' true true &');
}
