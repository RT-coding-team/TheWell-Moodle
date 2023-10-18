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
 * Retrieves feedback data from the database and returns as a PHP array.
 */
class FeedbackSerializer
{
    /**
     * An instance of Moodle's database
     */
    protected $db = null;

    /**
     * The feedback being serialized
     */
    protected $feedback;

    /**
     * The query for getting results
     */
    protected $query = "SELECT DISTINCT '' || fv.completed || '-' || fv.item as uniqueid, fc.id as fcid, u.id as userid, u.firstname, u.lastname, " .
    "fc.timemodified, fi.id as fiid, fi.name as question, fi.label, fi.presentation, fi.typ as questiontype, " .
    "fv.value as answer FROM mdl_feedback_value AS fv INNER JOIN mdl_feedback_completed AS fc ON fc.id = fv.completed " .
    "INNER JOIN mdl_user as u ON fc.userid = u.id INNER JOIN mdl_feedback_item" .
    " as fi ON fv.item = fi.id WHERE fc.feedback = :feedbackid AND fi.typ NOT IN ('info', 'label', 'pagebreak', 'captcha')";

    /**
     * Build the class
     * 
     * @param integer   $id                 The feedback id
     * @param object    $database           The Moodle database object
     */
    public function __construct($id, $database)
    {
        $this->db = $database;
        $this->feedback = $this->db->get_record('feedback', ['id' => $id]);
    }

    /**
     * Get the details about the feedback
     * 
     * @return array    The details about the feedback|empty if no feedback exists
     */
    public function details()
    {
        if (!$this->feedback) {
            return [];
        }

        return [
            'id'            =>  $this->feedback->id,
            'anonymous'     =>  ($this->feedback->anonymous == 1),
            'type'          =>  'feedback',
            'name'          =>  $this->feedback->name,
            'intro'         =>  strip_tags($this->feedback->intro),
            'modified_on'   =>  $this->feedback->timemodified,
        ];
    }

    /**
     * Get the results of the feedback request
     * 
     * @return array    The results
     */
    public function results()
    {
        if (!$this->feedback) {
            return [];
        }

        $submissions = $this->db->get_records_sql(
            $this->query,
            ['feedbackid'  =>  $this->feedback->id]
        );
        // We will hold the data in this array then push into the result array. We need this array keyed to the user id.
        $data = [];
        foreach ($submissions as $submission) {
            if (!array_key_exists($submission->userid, $data)) {
                $userid = ($this->feedback->anonymous == 1) ? -1 : $submission->userid;
                $firstname = ($this->feedback->anonymous == 1) ? 'Anonymous' : $submission->firstname;
                $lastname = ($this->feedback->anonymous == 1) ? '' : $submission->lastname;
                $data[$submission->userid] = [
                    'user_id'           =>  $userid,
                    'firstname'         =>  $firstname,
                    'lastname'          =>  $lastname,
                    'answers'           =>  [],
                    'modified_on'       =>  $submission->timemodified
                ];
            }
            $selected = '';
            $scale = null;
            switch($submission->questiontype) {
                case 'multichoicerated':
                    // Answer is in presentation and looks like this: r>>>>>0####Democracy|1####Anarchy|2####Oligarchy|3####Dictatorship|4####Royalty by Decree
                    $cleaned = str_replace('####', ' - ', str_replace(["\r\n", "\r", "\n"], "", substr($submission->presentation, 5)));
                    $choices = explode('|', $cleaned);
                    $answerIndex = intval($submission->answer) - 1;
                    $selected = (array_key_exists($answerIndex, $choices)) ? $choices[$answerIndex] : '';
                    break;
                case 'multichoice':
                    // Answer is in presentation and looks like this: r>>>>>King Arthur\r|Merlin\r|Sir Gallahad\r|Myself
                    $cleaned = str_replace(["\r\n", "\r", "\n"], "", substr($submission->presentation, 5));
                    $choices = explode('|', $cleaned);
                    $answerIndex = intval($submission->answer) - 1;
                    $selected = (array_key_exists($answerIndex, $choices)) ? $choices[$answerIndex] : '';
                    break;
                case 'numeric':
                    $scalePieces = explode('|', $submission->presentation);
                    $scale = [
                        'min'   =>  $scalePieces[0],
                        'max'   =>  $scalePieces[1]
                    ];
                default:
                    $selected = $submission->answer;
                    break;
            }
            $answer = [
                'feedback_item' =>  [
                    'id'        =>  $submission->fiid,
                    'label'     =>  $submission->label,
                    'question'  =>  $submission->question
                ],
                'answer'    =>  $selected,
                'scale'     =>  $scale
            ];
            $data[$submission->userid]['answers'][] = $answer;
        }
        $results = [];
        foreach ($data as $item) {
            $results[] = $item;
        }

        return $results;
    }
}