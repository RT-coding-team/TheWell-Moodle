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
 * Email support.
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once($CFG->libdir . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'PHPMailer.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'ReportingUtility.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Settings.php');

$email = Settings::get('server_siteadmin_email');
$reporting = new ReportingUtility(dirname(__FILE__), false);
$logFilePath = $reporting->getLogFilePath();
$report = $reporting->read();
if (isset($_SERVER['HTTP_HOST'])) {
    $from = 'server@' .  $_SERVER['HTTP_HOST'];
} else {
    $from = 'server@thewell.com';
}

header('Content-type: application/json');
if ((!$email) || ($email === '')) {
    echo json_encode([
        'success'   =>  false,
        'reason'    =>  'You need to specify a support email on the box.'
    ]);
    exit();
}
if ((!isset($_GET['token'])) || ($_GET['token'] === '')) {
    echo json_encode([
        'success'   =>  false,
        'reason'    =>  'You must supply a valid token.'
    ]);
    exit();
}
if ((!isset($report->support_token)) || ($report->support_token === '')) {
    echo json_encode([
        'success'   =>  false,
        'reason'    =>  'The support token is empty. Are you sure you had an error.'
    ]);
    exit();
}
if ($report->support_token !== $_GET['token']) {
    echo json_encode([
        'success'   =>  false,
        'reason'    =>  'Your token does not match.'
    ]);
    exit();
}

$mail = new PHPMailer(true);
try {
    $mail->setFrom($from, 'The Well Server');
    $mail->addAddress($email, $email);
    $mail->addAttachment($logFilePath);
    $mail->Subject = 'The Well Sync Issue';
    $mail->Body    = 'We are experiencing a problem with syncing our Well device with the API. Please help.';
    $mail->send();
} catch (Exception $e) {
    echo json_encode([
        'success'   =>  false,
        'reason'    =>  'Unable to send the email: ' . $mail->ErrorInfo
    ]);
    exit();
}

$reporting->generateSupportToken(true, true);
echo json_encode([
    'success'   =>  true,
    'reason'    =>  'Email sent.'
]);
