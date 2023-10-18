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
 * Sync the current course tests and quiz reports with the local API.
 *
 * If you want to use on command line, use `php sync_course_testing_reports.php API_URL API_TOKEN`.
 */
define('CLI_SCRIPT', true);

$url = '';
$token = '';
if ((isset($argv)) && (isset($argv[1]))) {
    $url = $argv[1];
}
if ((isset($argv)) && (isset($argv[2]))) {
    $token = $argv[2];
}
if (empty($url)) {
    echo "\r\nYou must provide a valid API url to post the information to.\r\n";
    exit;
}

set_time_limit(0);

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'course' . DIRECTORY_SEPARATOR . 'lib.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'CurlUtility.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'serializers' . DIRECTORY_SEPARATOR . 'AssignmentSerializer.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'serializers' . DIRECTORY_SEPARATOR . 'FeedbackSerializer.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'serializers' . DIRECTORY_SEPARATOR . 'QuizSerializer.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'serializers' . DIRECTORY_SEPARATOR . 'SurveySerializer.php');

$courses = get_courses();
$approvedActivities = ['assign', 'feedback', 'quiz', 'survey'];

$assignments = [];
$feedback = [];
$quizzes = [];
$surveys = [];
$ids = [];
foreach ($courses as $course) {
    if (intval($course->id) === 1) {
        continue;
    }
    $ids[] = $course->id;
    $courseDetails = [
        'id'            =>  $course->id,
        'fullname'      =>  $course->fullname,
        'shortname'     =>  $course->shortname,
        'summary'       =>  strip_tags($course->summary),
        'created_on'    =>  $course->timecreated,
        'modified_on'   =>  $course->timemodified
    ];
    $activities = get_array_of_activities($course->id);
    foreach ($activities as $activity) {
        if (!in_array($activity->mod, $approvedActivities)) {
            continue;
        }
        $serializer = null;
        if ($activity->mod === 'quiz') {
            $serializer = new QuizSerializer($activity->id, $DB);
            $activityDetails = $serializer->details();
            if (!empty($activityDetails)) {
                $activityDetails['results'] = $serializer->results($course->id);
            }
        } else if ($activity->mod === 'survey') {
            $serializer = new SurveySerializer($activity->id, $DB);
            $activityDetails = $serializer->details();
            if (!empty($activityDetails)) {
                $activityDetails['results'] = $serializer->results($course->id, $activity->cm);
            }
            $activityDetails['survey_type'] = $serializer->surveyType;
        } else if ($activity->mod === 'assign') {
            $serializer = new AssignmentSerializer($activity->id, $DB);
            $activityDetails = $serializer->details();
            if (!empty($activityDetails)) {
                $activityDetails['results'] = $serializer->results($course->id, $activity->cm);
            }
        } else if ($activity->mod === 'feedback') {
            $serializer = new FeedbackSerializer($activity->id, $DB);
            $activityDetails = $serializer->details();
            if (!empty($activityDetails)) {
                $activityDetails['results'] = $serializer->results();
            }
        }
        if (!empty($activityDetails)) {
            $data = [
                'course'    =>  $courseDetails,
                'activity'  =>  $activityDetails
            ];
            if ($activity->mod === 'quiz') {
                $quizzes[] = $data;
            } else if ($activity->mod === 'survey') {
                $surveys[] = $data;
            } else if ($activity->mod === 'assign') {
                $assignments[] = $data;
            } else if ($activity->mod === 'feedback') {
                $feedback[] = $data;
            }
        }
    }
}
/**
 * Send everything to the API
 */
$curl = new CurlUtility($url, $token);
echo "SYNCING COURSES:\r\n";
print_r(json_encode($ids, JSON_NUMERIC_CHECK));
$curl->makeRequest('/api/lms/stats/sync-courses', 'POST', json_encode($ids), null, true);
if ($curl->responseCode === 201) {
    echo "\r\nCourses have been successfully synced to the API.\r\n";
} else {
    echo "\r\nError! Courses were not synced to the API.\r\n";
}
if (!empty($assignments)) {
    echo "ASSIGNMENTS:\r\n";
    print_r(json_encode($assignments, JSON_NUMERIC_CHECK));

    $curl->makeRequest('/api/lms/stats/assignments', 'POST', json_encode($assignments), null, true);
    if ($curl->responseCode === 201) {
        echo "\r\nAssignments have been successfully sent to the API.\r\n";
    } else {
        echo "\r\nError! Assignments were not sent to the API.\r\n";
    }
}
if (!empty($feedback)) {
    echo "\r\nFEEDBACK:\r\n";
    print_r(json_encode($feedback, JSON_NUMERIC_CHECK));

    $curl->makeRequest('/api/lms/stats/feedback', 'POST', json_encode($feedback), null, true);
    if ($curl->responseCode === 201) {
        echo "\r\nFeedback has been successfully sent to the API.\r\n";
    } else {
        echo "\r\nError! Feedback were not sent to the API.\r\n";
    }
}
if (!empty($quizzes)) {
    echo "\r\nQUIZZES:\r\n";
    print_r(json_encode($quizzes, JSON_NUMERIC_CHECK));

    $curl->makeRequest('/api/lms/stats/quizzes', 'POST', json_encode($quizzes), null, true);
    if ($curl->responseCode === 201) {
        echo "\r\nQuizzes have been successfully sent to the API.\r\n";
    } else {
        echo "\r\nError! Quizzes were not sent to the API.\r\n";
    }
}
if (!empty($surveys)) {
    echo "\r\nSURVEYS:\r\n";
    print_r(json_encode($surveys, JSON_NUMERIC_CHECK));

    $curl->makeRequest('/api/lms/stats/surveys', 'POST', json_encode($surveys), null, true);
    if ($curl->responseCode === 201) {
        echo "\r\nSurveys have been successfully sent to the API.\r\n";
    } else {
        echo "\r\nError! Surveys were not sent to the API.\r\n";
    }
}
