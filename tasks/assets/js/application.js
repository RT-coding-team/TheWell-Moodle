/**
 * The given support token.
 *
 * @type {string}
 */
var token = '';
/**
 * Get the step icon based on the status
 *
 * @param  {string} status The status (pending, started, errored, completed)
 * @return {string}        The icon or an empty string
 */
function getStepIcon(status) {
  if (status == 'pending') {
    return '<i class="far fa-square"></i>';
  } else if (status == 'started') {
    return '<i class="fas fa-spinner fa-spin"></i>';
  } else if (status == 'errored') {
    return '<i class="fas fa-exclamation-triangle"></i>';
  } else if (status == 'completed') {
    return '<i class="far fa-check-square"></i>';
  } else {
    return '';
  }
}
/**
 * Populate the error logs panel.
 *
 * @param  {array} logs   An array of errorlog data
 * @return {void}
 */
function populateErrors(logs) {
  $('#error-logs-output .card-body').html('');
  $.each(logs, function(index, log) {
    var html = '<p><em>' + log.pretty_time + ':</em> ' + log.message + ' <span class="badge badge-pill badge-primary">' + log.category + '</span></p>';
    $('#error-logs-output .card-body').append(html);
  });
}
/**
 * Populate the logs panel.
 *
 * @param  {array} logs   An array of log data
 * @return {void}
 */
function populateLogs(logs) {
  $('#logs-output .card-body').html('');
  $.each(logs, function(index, log) {
    var html = '<p><em>' + log.pretty_time + ':</em> ' + log.message + ' <span class="badge badge-pill badge-primary">' + log.category + '</span></p>';
    $('#logs-output .card-body').append(html);
  });
}
/**
 * Populate the stats fields
 *
 * @param  {object} stats The stats data
 * @return {void}
 */
function populateStats(stats) {
  var parent = $('#statistics .statistics-table');
  parent.find('.info-status').first().text(stats.status);
  if (stats.hasOwnProperty('box_id')) {
      parent.find('.info-box-id').first().text(stats.box_id);
  } else {
    parent.find('.info-box-id').first().text('-');
  }
  if (stats.hasOwnProperty('last_time_synced_pretty')) {
    parent.find('.info-time-synced').first().text(stats.last_time_synced_pretty);
  } else {
    parent.find('.info-time-synced').first().text('-');
  }
  if (stats.hasOwnProperty('total_messages_sent')) {
    parent.find('.info-sent-messages').first().text(stats.total_messages_sent);
  } else {
    parent.find('.info-sent-messages').first().text('-');
  }
  if (stats.hasOwnProperty('total_attachments_sent')) {
    parent.find('.info-sent-attachments').first().text(stats.total_attachments_sent);
  } else {
    parent.find('.info-sent-attachments').first().text('-');
  }
  if (stats.hasOwnProperty('total_attachments_sent_failed')) {
    parent.find('.info-sent-attachments-failed').first().text(stats.total_attachments_sent_failed);
  } else {
    parent.find('.info-sent-attachments-failed').first().text('-');
  }
  if (stats.hasOwnProperty('total_messages_received_completed')) {
    parent.find('.info-messages-received').first().text(stats.total_messages_received_completed);
  } else {
    parent.find('.info-messages-received').first().text('-');
  }
  if (stats.hasOwnProperty('total_messages_received_failed')) {
      parent.find('.info-messages-received-failed').first().text(stats.total_messages_received_failed);
  } else {
      parent.find('.info-messages-received-failed').first().text('-');
  }
}
/**
 * Populate the steps
 *
 * @param  {object} steps The steps progress
 * @return {void}
 */
function populateSteps(steps) {
  var parent = $('#statistics .steps-table');
  parent.find('.step-started').first().html(getStepIcon(steps.script));
  parent.find('.step-last-time-sync').first().html(getStepIcon(steps.check_last_sync));
  parent.find('.step-sending-roster').first().html(getStepIcon(steps.sending_roster));
  parent.find('.step-sending-messages').first().html(getStepIcon(steps.sending_messages));
  parent.find('.step-sending-attachments').first().html(getStepIcon(steps.sending_attachments));
  parent.find('.step-receiving-messages').first().html(getStepIcon(steps.receiving_messages));
  parent.find('.step-finished').first().html(getStepIcon(steps.script));
}
/**
 * Poll the server for more information about the sync script
 *
 * @return {void}
 */
function pollServer() {
  $.get('/local/chat_attachments/report.json', function(data) {
    populateLogs(data.logs);
    populateErrors(data.errors);
    populateStats(data.results);
    populateSteps(data.steps);
    if (data.progress !== null) {
      $('#current-progress h5').text(data.progress.title);
      var completed = (data.progress.current + data.progress.error);
      var total = (completed/data.progress.total) * 100;
      $('#current-progress .progress-bar').css('width', total + '%');
      $('#current-progress .progress-bar').attr('aria-valuenow', total);
      $('#current-progress .progress-bar').text(completed + ' / ' + data.progress.total);
      $('#current-progress').removeClass('d-none');
    } else {
      setTimeout(function() {
        $('#current-progress').addClass('d-none');
      }, 1000);
    }
    if (data.results.status === 'started') {
      $('button#sync').prop('disabled', true);
      $('button#sync i').addClass('fa-spin');
    } else {
      $('button#sync').prop('disabled', false);
      $('button#sync i').removeClass('fa-spin');
    }
    if (!data.support_token) {
      token = '';
      $('#report-problems').prop('disabled', true);
    } else {
      token = data.support_token;
      $('#report-problems').prop('disabled', false);
    }
    setTimeout(pollServer, 2000);
  }).fail(function() {
    setTimeout(pollServer, 2000);
  });
}

$(function() {
  $('button#sync').on('click', function(event) {
    event.stopPropagation();
    $('#message-holder').html('');
    $('button#sync').prop('disabled', true);
    $('button#sync i').addClass('fa-spin');
    $.get('./sync.php').then(function() {});
    return false;
  });
  $('#report-problems').on('click', function(event) {
    event.stopPropagation();
    if (token === '') {
      $('#message-holder').html('<div class="alert alert-warning" role="alert">' + strings.tasks_message_no_problems + '</div>');
      $('html, body').animate({ scrollTop: 0 }, 'slow');
      return false;
    }
    $.get('/local/chat_attachments/email_support.php?token=' + token).then(function(data) {
      if (data.success) {
        $('#message-holder').html('<div class="alert alert-primary" role="alert">' + strings.tasks_message_reporting_success + '</div>');
      } else {
        $('#message-holder').html('<div class="alert alert-warning" role="alert">' + strings.tasks_message_reporting_error + '</div>');
        console.error(data.reason);
      }
      $('html, body').animate({ scrollTop: 0 }, 'slow');
    });
    return false;
  });
  pollServer();
});
