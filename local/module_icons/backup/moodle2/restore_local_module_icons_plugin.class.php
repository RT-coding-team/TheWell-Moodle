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

defined('MOODLE_INTERNAL') || die();

/**
 * Restore the module icons
 */
class restore_local_module_icons_plugin extends restore_local_plugin {
        
    /**
     * Define the paths we need to handle from the XML file
     *
     * @return array    The paths we want to handle
     */
    protected function define_module_plugin_structure() {
        $paths = [];

        // This defines the postfix of 'process_*' below.
        $name = 'plugin_local_module_icons';
        $path = $this->get_pathfor('/');
        $paths[] = new restore_path_element($name, $path);

        return $paths;
    }

    /**
     * Process the data from the XML file. It will fire for each module icon.
     *
     * @param  mixed $data The data to process
     *
     * @return void
     */
    public function process_plugin_local_module_icons($data) {
        global $DB;
        $data = (object) $data;
        $DB->execute(
            'INSERT INTO {local_module_icons} (course_id, course_module_id, icon) VALUES (?, ?, ?)',
            [$this->task->get_courseid(), $this->task->get_moduleid(), $data->icon]
        );
    }

}