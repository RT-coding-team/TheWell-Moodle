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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Handle the upgrading of the database
 */
function xmldb_local_module_icons_upgrade($oldversion) {
    global $CFG, $DB, $PAGE;
    if ($oldversion < 10301) {
        if ($CFG->version > 2021051700) {
            $path = $PAGE->theme->dir . DIRECTORY_SEPARATOR . 'pix_core' . DIRECTORY_SEPARATOR . 'mi';
            // Moodle 4.0+ uses svg icons so replace them
            // Iterate all icons in the database
            $icons = $DB->get_records('local_module_icons');
            foreach ($icons as $icon) {
                // Check if there is a svg equivalent
                $filename = substr($icon->icon, 0, strrpos($icon->icon, '.'));
                $svg = $path . DIRECTORY_SEPARATOR . $filename . '.svg';
                if (file_exists($svg)) {
                    // If there is, replace the icon with the svg equivalent
                    $icon->icon = str_replace('.png', '.svg', $icon->icon);
                    $DB->update_record('local_module_icons', $icon);
                }
            }
        }
        upgrade_plugin_savepoint(true, 10301, 'plugin', 'local_module_icons');
    }

    return true;
}
