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
 * Various PHP utility functions.
 */
/**
 * Check whether the given URL exists.
 *
 * @param  string $url The URL
 * @return boolean     yes|no
 */
function remote_file_exists($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($retcode === 200);
}
/**
 * Download the remote archive locally.
 *
 * @param  string $src  The source file
 * @param  string $dest The destination file
 * @return void
 */
function download_remote_archive($src, $dest) {
    file_put_contents($dest, file_get_contents($src));
}
