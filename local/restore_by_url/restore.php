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
/**
 * A custom form to retrieve the URL for the backup file
 */
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) .DIRECTORY_SEPARATOR . 'utilities.php');

admin_externalpage_setup('local_restore_by_url_settings');
$error = '';
$destination = '/tmp/download.mbz'; // Always use this filename

if (!empty($_POST)) {
    if ((!array_key_exists('form_remote_url_field', $_POST)) || ($_POST['form_remote_url_field'] === '')) {
        $error = get_string('form_error_no_url_provided', 'local_restore_by_url');
    }
    $remoteFile = $_POST['form_remote_url_field'];
    if (!remote_file_exists($remoteFile)) {
        $error = get_string('form_error_url_missing', 'local_restore_by_url');
    }

    if ($error === '') {
        set_time_limit(0);
        $scriptPath = '/usr/local/connectbox/bin/ConnectBoxManage.sh';
        exec('sudo ' . $scriptPath . ' set course-download ' . $remoteFile . ' /tmp/coursedownload.log 2>&1');
        header('Location: ' . new moodle_url('/local/restore_by_url/restore.php?success=true'));
        exit();
    }
}
$success = '';
if ((!empty($_GET)) && ($_GET['success'] === 'true')) {
    $success = 'You course has been restored!';
}

echo $OUTPUT->header();
?>
<form id="resture-by-url-form" action="<?php echo new moodle_url('/local/restore_by_url/restore.php'); ?>" method="post">
    <div class="settingsform">
        <h2><?php echo get_string('pluginname', 'local_restore_by_url'); ?></h2>
        <div id="message-holder">
            <?php
                if ($error !== '') {
                    echo '<p style="font-style: italic; color: red;">' . $error . '</p>';
                }
                if ($success !== '') {
                    echo '<p style="font-style: italic; color: blue;">' . $success . '</p>';
                }
            ?>
        </div>
        <fieldset>
            <div class="clearer"></div>
            <div id="admin-form_remote_url_field" class="form-item row">
                <div class="form-label col-sm-3 text-sm-right">
                    <label for="id_s_local_chat_attachments_local_restore_by_url"><?php echo get_string('form_remote_url_field', 'local_restore_by_url'); ?></label>
                    <span class="form-shortname d-block small text-muted">local_chat_attachments | form_remote_url_field</span>
                </div>
                <div class="form-setting col-sm-9">
                    <div class="form-text defaultsnext">
                        <input type="text" name="form_remote_url_field" value="" size="30" id="id_form_remote_url_field" class="form-control text-ltr">
                    </div>
                    <div class="form-defaultinfo text-muted text-ltr">Default: Empty</div>
                    <div class="form-description mt-3"><p><?php echo get_string('form_remote_url_field_desc', 'local_restore_by_url'); ?></p></div>
                </div>
            </div>
        </fieldset>
        <div class="row">
            <div class="offset-sm-3 col-sm-3">
                <button type="submit" class="btn btn-primary" onClick="this.disabled=true; this.innerText='<?php echo get_string('form_remote_url_restoring', 'local_restore_by_url'); ?>';">
                    <?php echo get_string('form_remote_url_submit', 'local_restore_by_url'); ?>
                </button>
            </div>
        </div>
    </div>
</form>
<?php
echo $OUTPUT->footer();
