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
 * Restore the course categories
 * 
 * NOTE: Since Moodle fires the process_ methods at random times, we wait until we have all
 * data ($categoryPath, $categories) before we process it.
 */
class restore_local_retain_categories_plugin extends restore_local_plugin {
        
    /**
     * The found category path for the course
     */
    protected $categoryPath;

    /**
     * All the categories from the previous Moodle instance
     */
    protected $categories;

    /**
     * Returns the paths to be handled by the plugin at course level
     *
     * @return array    The paths to process
     */
    protected function define_course_plugin_structure() {
        $paths = array();
        /**
         * Because of using get_recommended_name() it is able to find the
         * correct path just by using the part inside the element name.
         */
        $categoriesPath = $this->get_pathfor('/categories');
        $paths[] = new restore_path_element('categories', $categoriesPath);
        $courseCategoryPath = $this->get_pathfor('/course_category');
        $paths[] = new restore_path_element('category_path', $courseCategoryPath);
        return $paths;
    }

    /**
     * Process retrieved categories. This is a JSON object that we parse and set to a class property to
     * be used later.
     *
     * @param  mixed $data  The data in the XML element
     *
     * @return void
     */
    public function process_categories($data) {
        $data = (object) $data;
        $cleaned = trim(str_replace(['//<![CDATA[', '//&lt;![CDATA[', '//]]>', '//]]&gt;'], '', $data->data));
        $this->categories = json_decode($cleaned);
        if (isset($this->categories) && isset($this->categoryPath)) {
            $this->setUpCategories();
        }
    }
        
    /**
     * Process the category path
     *
     * @param  mixed $data  The data in the XML element
     *
     * @return void
     */
    public function process_category_path($data) {
        $data = (object)$data;
        $this->categoryPath = $data->path;
        if (isset($this->categories) && isset($this->categoryPath)) {
            $this->setUpCategories();
        }
    }
    
    /**
     * set up the categories and add the course to the appropriate category
     *
     * @return void
     */
    private function setUpCategories() {
        global $DB;
        // Break the path for the course's category (remove empty elements)
        $ids = array_filter(explode('/', $this->categoryPath));
        // Iterate the path top down (category without parent first)
        $parentId = 0;
        $newCategoryId = -1;
        foreach ($ids as $id) {
            // find the details of the category from the past site
            $found = null;
            foreach ($this->categories as $category) {
                if (intval($category->id) === intval($id)) {
                    $found = $category;
                    break;
                }
            }
            if (!$found) {
                // No need.  We do not have the category data.
                $newCategoryId = -1;
                break;
            }
            // See if the category exists. Path should be the same. parent?
            $exist = $DB->get_record('course_categories', ['name' => $found->name, 'parent' => $parentId]);
            if (!$exist) {
                // If it doesn't exist add it
                $new = new stdClass();
                $new->name = $found->name;
                $new->description = $found->description;
                $new->descriptionformat = $found->descriptionformat;
                $new->visible = 0;
                $new->parent = $parentId;
                $new->idnumber = null;
                $new = core_course_category::create($new);
                $parentId = $new->id;
                $newCategoryId = $new->id;
            } else {
                // set the courses category to the new id
                $parentId = $exist->id;
                $newCategoryId = $exist->id;
            }
        }
        if ($newCategoryId !== -1) {
            // Update the course category
            $course = new stdClass();
            $course->id = $this->task->get_courseid();
            $course->category = $newCategoryId;
            $DB->update_record('course', $course);
        }
    }

}