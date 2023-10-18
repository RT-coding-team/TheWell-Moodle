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
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'dmllib.php');

/**
 * Retrieves assignment data from the database and returns as a PHP array.
 */
class AssignmentSerializer
{
    /**
     * Our assignment that is being serialized
     */
    protected $assignment;
    /**
     * An instance of Moodle's database
     */
    protected $db = null;
    /**
     * The query to get the results. IN is dynamically added.
     */
    protected $query = "SELECT u.id, u.firstname, u.lastname, u.middlename , u.id as userid, s.status as status, " .
    "s.id as submissionid, s.timecreated, s.timemodified, s.attemptnumber as attemptnumber, g.id as gradeid, g.grade as grade " .
    "FROM {user} u LEFT JOIN {assign_submission} s ON u.id = s.userid AND s.assignment = :assign1 AND s.latest = 1 LEFT JOIN " .
    "{assign_grades} g ON g.assignment = :assign2 AND u.id = g.userid AND (g.attemptnumber = s.attemptnumber " .
    "OR s.attemptnumber IS NULL) WHERE u.id IN";

    /**
     * Build the class
     * 
     * @param integer   $id                 The quiz id
     * @param object    $database           The Moodle database object
     */
    public function __construct($id, $database)
    {
        $this->db = $database;
        $this->assignment = $this->db->get_record('assign', ['id' => $id]);
    }

    /**
     * Get the details about the assignment
     * 
     * @return array    The details about the assignment|empty if no assignment exists
     */
    public function details()
    {
        if (!$this->assignment) {
            return [];
        }

        return [
            'id'            =>  $this->assignment->id,
            'type'          =>  'assignment',
            'name'          =>  $this->assignment->name,
            'intro'         =>  strip_tags($this->assignment->intro),
            'max_grade'     =>  $this->assignment->grade,
            'due_on'        =>  $this->assignment->duedate,
            'modified_on'   =>  $this->assignment->timemodified,
        ];
    }

    /**
     * Get the results from the assignment.
     * 
     * @param integer $courseId         The id of the course
     * @param integer $courseModuleId   The id for the course module
     * 
     * @return array                    The results
     */
    public function results($courseId, $courseModuleId)
    {
        if (!$this->assignment) {
            return [];
        }

        $results = [];
        $context = context_module::instance($courseModuleId);
        $users = get_enrolled_users($context, "mod/assign:submit", 0, 'u.id');
        if (empty($users)) {
            return [];
        }
        $params = [
            'assign1'   =>  $this->assignment->id,
            'assign2'   =>  $this->assignment->id 
        ];
        $labels = [];
        foreach ($users as $user) {
            $labels[] = ':user' . $user->id;
            $params['user' . $user->id] = $user->id;
        }
        $submissions = $this->db->get_records_sql($this->query . ' (' . implode(',', $labels) .')', $params);
        foreach ($submissions as $submission) {
            $results[] = [
                'submission_id'     =>  $submission->submissionid,
                'user_id'           =>  $submission->userid,
                'firstname'         =>  $submission->firstname,
                'lastname'          =>  $submission->lastname,
                'grade'             =>  $submission->grade,
                'state'             =>  $submission->status,
                'total_attempts'    =>  $submission->attemptnumber,
                'created_on'        =>  $submission->timecreated,
                'modified_on'       =>  $submission->timemodified
            ];
        }
        return $results;
    }
}