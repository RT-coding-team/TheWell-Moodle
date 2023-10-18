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
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'moodlelib.php');
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'accesslib.php');

/**
 * Retrieves survey data from the database and returns as a PHP array that can be serialized.
 */
class SurveySerializer
{
    /**
     * The type of survey. This is only set after results is called because
     * the type is determined from the survey_questions table.
     */
    public $surveyType = '';
    /**
     * An instance of Moodle's database
     */
    protected $db = null;

    /**
     * The survey being serialized
     */
    protected $survey;

    /**
     * This is used to determine the type of survey. We use the first three letters of the
     * survey_questions.text to determine the type of survey.
     */
    protected $surveyTypes = [
        'att'   =>  'ATTLS',
        'col'   =>  'COLLES',
        'ciq'   =>  'CI'
    ];

    /**
     * Build the class
     * 
     * @param integer   $id         The survey id
     * @param object    $database   The Moodle database object
     */
    public function __construct($id, $database)
    {
        $this->db = $database;
        $this->survey = $this->db->get_record('survey', ['id'   =>  $id]);
    }

    /**
     * Get the details about the survey
     * 
     * @return array    The details about the survey|empty if no survey exists
     */
    public function details()
    {
        if (!$this->survey) {
            return [];
        }
        return [
            'id'            =>  $this->survey->id,
            'type'          =>  'survey',
            'name'          =>  $this->survey->name,
            'intro'         =>  strip_tags($this->survey->intro),
            'created_on'    =>  $this->survey->timecreated,
            'modified_on'   =>  $this->survey->timemodified
        ];
    }

    /**
     * Get the results from the survey.  Adapted from code in mod/survey/download.php.
     * 
     * @param integer $courseId         The id of the course
     * @param integer $courseModuleId   The id for the course module
     * 
     * @return array                    The results
     */
    public function results($courseId, $courseModuleId)
    {
        if (!$this->survey) {
            return [];
        }

        $results = [];
        // Get and collate all answers in a single array
        $answers = $this->db->get_records('survey_answers', ['survey'   =>  $this->survey->id], 'time ASC');
        if (!$answers) {
            return [];
        }
        $context = context_module::instance($courseModuleId);
        $users = get_users_by_capability($context, 'mod/survey:participate', '', '', '', '', '', null, false);
        /**
         * The questions is a comma seperated array of ids in the correct order.
         * Get a correct order of the questions. We will reset questions later, so
         * do not use it as the ordered questions.
         */
        $order = explode(',', $this->survey->questions);
        $questions = $this->db->get_records_list('survey_questions', 'id', $order);
        $orderedQuestions = [];
        // Not sure why they use this?
        $isVirtualScale = false;
        // To get the survey type, we look at the first three letters of the survey_questions.text field
        // It helps us determine it.
        $typeKeys = array_keys($this->surveyTypes);
        foreach ($order as $id) {
            // We need to set type of survey
            $key = substr($questions[$id]->text, 0, 3);
            if (
                (empty($this->surveyType)) &&
                (in_array($key, $typeKeys))
            ) {
                $this->surveyType = $this->surveyTypes[$key];
            }
            $orderedQuestions[$id] = $questions[$id];
            if (!$isVirtualScale && $questions[$id]->type < 0) {
                $isVirtualScale = true;
            }
        }
        // Will hold the questions with nested questions in order
        $nestedOrder = [];
        //Not sure about this code?
        foreach ($orderedQuestions as $id => $question) {
            if (!empty($question->multi)) {
                $multiIds = explode(',', $questions[$id]->multi);
                foreach ($multiIds as $subId) {
                    if (!empty($orderedQuestions[$subId]->type)) {
                        $orderedQuestions[$subId]->type = $questions[$id]->type;
                    }
                }
            } else {
                $multiIds = [$id];
            }
            if ($isVirtualScale && $questions[$id]->type < 0) {
                $nestedOrder[$id] = $multiIds; 
            } else if (!$isVirtualScale && $question->type >= 0) {
                $nestedOrder[$id] = $multiIds;
            } else {
                $nestedOrder[$id] = [];
            }
        }
        /**
         * We need to collect the questions for nested questions
         */
        $reverseNestedOrder = [];
        foreach ($nestedOrder as $questionId => $subIds) {
            foreach ($subIds as $subId) {
                $reverseNestedOrder[$subId] = $questionId;
            }
        }
        $allQuestions = array_merge(
            $questions,
            $this->db->get_records_list('survey_questions', 'id', array_keys($reverseNestedOrder))
        );
        // array_merge() messes up the keys so reinstate them
        $questions = [];
        foreach ($allQuestions as $question) {
            $questions[$question->id] = $question;
            // Let us also get the text of the question
            $questions[$question->id]->text = get_string($questions[$question->id]->text, 'survey');
        }
        /**
         * Collect answers and the user information into an array
         */
        $data = [];
        foreach ($answers as $answer) {
            if (isset($users[$answer->userid])) {
                $questionId = $answer->question;
                if (!array_key_exists($answer->userid, $data)) {
                    $data[$answer->userid] = [
                        'time'  =>  $answer->time
                    ];
                }
                $data[$answer->userid][$questionId]['answer1'] = $answer->answer1;
                $data[$answer->userid][$questionId]['answer2'] = $answer->answer2;
            }
        }
        foreach ($data as $userId => $rest) {
            $user = $this->db->get_record('user', ['id' =>  $userId]);
            if (!$user) {
                continue;
            }
            $notes = $this->db->get_record('survey_analysis', ['survey' =>  $this->survey->id, 'userid' =>  $userId]);
            if (!$notes) {
                $notes = 'No notes made.';
            }
            $result = [
                'user_id'       =>  $user->id,
                'firstname'     =>  $user->firstname,
                'lastname'      =>  $user->lastname,
                'notes'         =>  $notes,
                'answers'       =>  [],
                'started_on'    =>  $data[$userId]['time']
            ];
            foreach ($nestedOrder as $nestedQuestions) {
                foreach ($nestedQuestions as $questionId) {
                    $answer = [];
                    $question = $questions[$questionId];
                    $answer['question'] = [
                        'id'    =>  $question->id,
                        'text'  =>  $question->text,
                    ];
                    $answer['answer'] = '';
                    if ($question->type == '2' || $question->type == '3') {
                        $answer['question'] .= ' (preferred)';
                    }
                    if (in_array($question->type, ['0', '1', '3', '-1']) && array_key_exists($questionId, $data[$userId])) {
                        $answer['answer'] = $data[$userId][$questionId]['answer1'];
                    }
                    // Not sure why 3 is in both if checks.
                    if (in_array($question->type, ['2', '3']) && array_key_exists($questionId, $data[$userId])) {
                        $answer['answer'] = $data[$userId][$questionId]['answer2'];
                    }
                    $result['answers'][] = $answer;
                }
            }
            $results[] = $result;
        }
        return $results;
    }
}
