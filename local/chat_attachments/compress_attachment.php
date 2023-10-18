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
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'FileStorageUtility.php');

if ((!isset($argv)) || (!isset($argv[1]))) {
    echo "Please provide a valid item id!\r\n";
    exit;
}
$itemId = $argv[1];
$fs = get_file_storage();
$systemContext = context_system::instance();
$storage = new FileStorageUtility($DB, $fs, $systemContext->id);
$file = $storage->findById($itemId);
if (!$file) {
    echo "The file does not exist!\r\n";
    exit;
}
/**
 * Check mime type to be sure we want to compress it.
 */
if (substr($file->mimetype, 0, 5) === 'video') {
    /**
     * Copy the file to a temp directory
     */
    $tempFile = $storage->retrieve($file->itemid, $file->filepath, $file->filename);
    $info = pathinfo($tempFile);
    $optFile = $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '-opt.' . $info['extension'];
    /**
     * Compress the file
     * @link https://superuser.com/a/859075
     */
    exec('ffmpeg -i ' . $tempFile . ' -c:v libx264 -crf 28 -profile:v baseline -level 3.0 -pix_fmt yuv420p -c:a aac -ac 2 -b:a 128k -movflags faststart -y ' . $optFile);
    /**
     * If the optimized file exists, and ffmpeg did not choke (0 byte file), replace the old file
     */
    if ((file_exists($optFile)) && (filesize($optFile) > 0)) {
        $storage->update($file->itemid, $file->filename, $optFile);
        unlink($optFile);
        unlink($tempFile);
    } else{
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        echo 'Unable to convert the file!';
        exit;
    }
}
echo "The file compression is complete.\r\n";
