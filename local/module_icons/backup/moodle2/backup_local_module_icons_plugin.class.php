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
 * @package    local_enemyquestions
 * @version    1.0
 * @copyright  &copy; 2015 Ray Morris <ray.morris@teex.tamu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Backup the module icons
 */
class backup_local_module_icons_plugin extends backup_local_plugin {
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }
 
    protected function define_module_plugin_structure() {

        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name(), [], ['course_id', 'course_module_id', 'icon']);
        $plugin->add_child($wrapper);

        $wrapper->set_source_sql(
            'SELECT * FROM {local_module_icons} WHERE course_id=:ci AND course_module_id=:cmi',
            array('ci' => backup::VAR_COURSEID, 'cmi' => backup::VAR_MODID)
        );

        return $plugin;
    }
}