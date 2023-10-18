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
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'mod' . DIRECTORY_SEPARATOR . 'quiz' . DIRECTORY_SEPARATOR . 'locallib.php');

/**
 * Retrieves quiz data from the database and returns as a PHP array.
 */
class QuizSerializer
{
    /**
     * An instance of Moodle's database
     */
    protected $db = null;
    /**
     * The query to get the results
     */
    protected $query = "SELECT DISTINCT '' || u.id || '#' || COALESCE(quiza.attempt, 0) AS uniqueid, " . 
    "(CASE WHEN (quiza.state = 'finished' AND NOT EXISTS ( SELECT 1 FROM {quiz_attempts} qa2 WHERE qa2.quiz = quiza.quiz " .
    "AND qa2.userid = quiza.userid AND qa2.state = 'finished' AND ( COALESCE(qa2.sumgrades, 0) > COALESCE(quiza.sumgrades, 0) " .
    "OR (COALESCE(qa2.sumgrades, 0) = COALESCE(quiza.sumgrades, 0) AND qa2.attempt < quiza.attempt) ))) THEN 1 ELSE 0 END) AS " .
    "gradedattempt, quiza.uniqueid AS usageid, quiza.id AS attempt, u.id AS userid, u.idnumber, u.firstname, u.lastname, " .
    "quiza.state, quiza.sumgrades, quiza.timefinish, quiza.timestart, CASE WHEN quiza.timefinish = 0 " .
    "THEN null WHEN quiza.timefinish > quiza.timestart THEN quiza.timefinish - quiza.timestart ELSE 0 END AS duration " .
    "FROM {user} u LEFT JOIN {quiz_attempts} quiza ON quiza.userid = u.id AND quiza.quiz = :quizid JOIN {user_enrolments} " .
    "uenroll ON uenroll.userid = u.id JOIN {enrol} enrol ON (enrol.id = uenroll.enrolid AND enrol.courseid = :courseid) JOIN " .
    "(SELECT DISTINCT userid FROM {role_assignments} as dra JOIN {context} dcon ON dra.contextid = dcon.id WHERE dcon.contextlevel " .
    "IN (10,40,50,70) AND dra.roleid IN (5)) ra ON ra.userid = u.id WHERE quiza.preview = 0 AND quiza.id IS NOT NULL AND " .
    "u.deleted = 0 AND u.id <> 1 AND u.deleted = 0";

    /**
     * The quiz being serialized
     */
    protected $quiz;

    /**
     * Build the class
     * 
     * @param integer   $id         The quiz id
     * @param object    $database   The Moodle database object
     */
    public function __construct($id, $database)
    {
        $this->db = $database;
        $this->quiz = $this->db->get_record('quiz', array('id' => $id));
    }

    /**
     * Get the details about the quiz
     * 
     * @return array an array of details about the quiz
     */
    public function details()
    {
        if (!$this->quiz) {
            return [];
        }

        return [
            'id'            =>  $this->quiz->id,
            'type'          =>  'quiz',
            'name'          =>  $this->quiz->name,
            'intro'         =>  strip_tags($this->quiz->intro),
            'max_grade'     =>  $this->quiz->grade,
            'created_on'    =>  $this->quiz->timecreated,
            'modified_on'   =>  $this->quiz->timemodified,
        ];
    }
    /**
     * Get the results of the quiz
     * 
     * @param integer $courseId The id of the course
     * 
     * @return array            An array of results
     */
    public function results($courseId)
    {
        if (!$this->quiz) {
            return [];
        }

        $results = [];
        $attempts = $this->db->get_records_sql(
            $this->query,
            ['quizid'  =>  $this->quiz->id, 'courseid' => $courseId]
        );
        foreach ($attempts as $attempt) {
            $results[] = [
                'user_id'       =>  $attempt->userid,
                'firstname'     =>  $attempt->firstname,
                'lastname'      =>  $attempt->lastname,
                'duration'      =>  $attempt->duration,
                'grade'         =>  quiz_rescale_grade($attempt->sumgrades, $this->quiz),
                'state'         =>  quiz_attempt_state_name($attempt->state),
                'started_on'    =>  $attempt->timestart,
                'finished_on'   =>  $attempt->timefinish,
            ];
        }
        return $results;
    }
}