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
 * Backup the course cateogires
 */
class backup_local_retain_categories_plugin extends backup_local_plugin {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }
     
    /**
     * Set up the structure for XML data. Example of the result stored in course/course.xml:
     *
     * <plugin_coursereport_retain_course_category_course>
     *     <course_category>
     *      <path>/7/8/9</path>
     *   </course_category>
     *   <categories>
     *      <data>A JSON object encoded and wrapped in CDATA tag</data>
     *    </categories>
     *  </plugin_coursereport_retain_course_category_course>
     *
     * @return object   The plugin element
     */
    protected function define_course_plugin_structure() {
        global $DB;
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);
        $categoryPath = new backup_nested_element('course_category', null, ['path']);
        $categoryPath->set_source_sql('SELECT c.category, cc.path
                                    FROM {course} c
                                    JOIN {course_categories} cc ON c.category = cc.id
                                    WHERE c.id = ?', array(backup::VAR_COURSEID));
        $wrapper->add_child($categoryPath);
        
        $categoryData = new backup_nested_element('categories', null, ['data']);
        $categories = $DB->get_records('course_categories');
        $data = array((object)array(
            'data'  =>  '//<![CDATA[' . json_encode($categories)  . '//]]>'
        ));
        $categoryData->set_source_array($data);
        $wrapper->add_child($categoryData);
        return $plugin;
    }

}