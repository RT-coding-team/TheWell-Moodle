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
 * Settings for this plugin.
 */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    /**
     * Add a link to the course settings
     */
    $ADMIN->add(
        'courses',
        new admin_externalpage(
            'local_restore_by_url_settings',
            new lang_string('pluginname', 'local_restore_by_url'),
            new moodle_url('/local/restore_by_url/restore.php')
        )
    );
}
