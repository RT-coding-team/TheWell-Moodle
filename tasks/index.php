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

require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config.php');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/application.css">
    <title><?php echo get_string('tasks_page_title', 'local_chat_attachments'); ?></title>
  </head>
  <body>
    <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow">
      <h5 class="my-0 mr-md-auto font-weight-normal"><?php echo get_string('brand', 'local_chat_attachments'); ?></h5>
    </div>
    <div class="container">
      <h2><?php echo get_string('tasks_title', 'local_chat_attachments'); ?></h2>
      <div id="message-holder"></div>
      <div id="current-progress" class="d-none">
        <h5></h5>
        <div class="progress mb-3">
          <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
      <div id="statistics">
        <div class="row">
          <div class="col-md">
            <table class="statistics-table table table-sm">
              <thead>
                <tr>
                  <th colspan="2"><?php echo get_string('tasks_label_statistics', 'local_chat_attachments'); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th scope="row"><?php echo get_string('tasks_label_status', 'local_chat_attachments'); ?></th>
                  <td class="info-status text-capitalize"></td>
                </tr>
                <tr>
                  <th scope="row"><?php echo get_string('tasks_label_box_id', 'local_chat_attachments'); ?></th>
                  <td class="info-box-id"></td>
                </tr>
                <tr>
                  <th scope="row"><?php echo get_string('tasks_label_last_time_synced', 'local_chat_attachments'); ?></th>
                  <td class="info-time-synced"></td>
                </tr>
                <tr>
                  <th scope="row"><?php echo get_string('tasks_label_messages_sent', 'local_chat_attachments'); ?></th>
                  <td class="info-sent-messages"></td>
                </tr>
                <tr>
                  <th scope="row"><?php echo get_string('tasks_label_attachments_sent', 'local_chat_attachments'); ?></th>
                  <td class="info-sent-attachments"></td>
                </tr>
                <tr>
                  <th scope="row"><?php echo get_string('tasks_label_attachments_sent_failed', 'local_chat_attachments'); ?></th>
                  <td class="info-sent-attachments-failed"></td>
                </tr>
                <tr>
                  <th scope="row"><?php echo get_string('tasks_label_messages_received', 'local_chat_attachments'); ?></th>
                  <td class="info-messages-received"></td>
                </tr>
                <tr>
                  <th scope="row"><?php echo get_string('tasks_label_messages_received_failed', 'local_chat_attachments'); ?></th>
                  <td class="info-messages-received-failed"></td>
                </tr>
                <tr>
                  <th scope="row"><?php echo get_string('tasks_label_missing_attachments', 'local_chat_attachments'); ?></th>
                  <td class="info-missing-attachments"></td>
                </tr>
              <tbody>
            </table>
          </div>
          <div class="col-md">
            <table class="steps-table table table-sm">
              <thead>
                <tr>
                  <th colspan="2"><?php echo get_string('tasks_label_steps', 'local_chat_attachments'); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="step-started"><i class="far fa-square"></i></td>
                  <td><?php echo get_string('tasks_label_started', 'local_chat_attachments'); ?></td>
                </tr>
                <tr>
                  <td class="step-last-time-sync"><i class="far fa-square"></i></td>
                  <td><?php echo get_string('tasks_label_check_last_sync', 'local_chat_attachments'); ?></td>
                </tr>
                <tr>
                  <td class="step-sending-roster"><i class="far fa-square"></i></td>
                  <td><?php echo get_string('tasks_label_sending_roster', 'local_chat_attachments'); ?></td>
                </tr>
                <tr>
                  <td class="step-sending-messages"><i class="far fa-square"></i></td>
                  <td><?php echo get_string('tasks_label_sending_messages', 'local_chat_attachments'); ?></td>
                </tr>
                <tr>
                  <td class="step-sending-attachments"><i class="far fa-square"></i></td>
                  <td><?php echo get_string('tasks_label_sending_attachments', 'local_chat_attachments'); ?></td>
                </tr>
                <tr>
                  <td class="step-receiving-messages"><i class="far fa-square"></i></td>
                  <td><?php echo get_string('tasks_label_receiving_messages', 'local_chat_attachments'); ?></td>
                </tr>
                <tr>
                  <td class="step-finished"><i class="far fa-square"></i></td>
                  <td><?php echo get_string('tasks_label_completed', 'local_chat_attachments'); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div id="accordion" class="mb-3">
        <div class="card">
          <div class="card-header" id="heading-logs">
            <h5 class="mb-0">
              <button class="btn btn-link" data-toggle="collapse" data-target="#logs-output" aria-expanded="true" aria-controls="logs-output">
                <?php echo get_string('tasks_button_logs', 'local_chat_attachments'); ?>
              </button>
            </h5>
          </div>

          <div id="logs-output" class="collapse" aria-labelledby="heading-logs" data-parent="#accordion">
            <div class="card-body">
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header" id="heading-error-logs">
            <h5 class="mb-0">
              <button class="btn btn-link" data-toggle="collapse" data-target="#error-logs-output" aria-expanded="true" aria-controls="error-logs-output">
                <?php echo get_string('tasks_button_errors', 'local_chat_attachments'); ?>
              </button>
            </h5>
          </div>

          <div id="error-logs-output" class="collapse" aria-labelledby="heading-error-logs" data-parent="#accordion">
            <div class="card-body">
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col">
          <button type="button" class="btn btn-primary btn-block" id="sync"><i class="fas fa-sync"></i> <?php echo get_string('tasks_button_sync', 'local_chat_attachments'); ?></button>
        </div>
        <div class="col">
          <button type="button" class="btn btn-secondary btn-block" id="report-problems" disabled="true"><i class="far fa-envelope"></i> <?php echo get_string('tasks_button_report_problems', 'local_chat_attachments'); ?></button>
        </div>
      </div>
    </div>
    <script type="text/javascript">
        var strings = {
            tasks_message_no_problems: '<?php echo get_string('tasks_message_no_problems', 'local_chat_attachments'); ?>',
            tasks_message_reporting_error: '<?php echo get_string('tasks_message_reporting_error', 'local_chat_attachments'); ?>',
            tasks_message_reporting_success: '<?php echo get_string('tasks_message_reporting_success', 'local_chat_attachments'); ?>',
        };
    </script>
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/application.js"></script>
  </body>
</html>
