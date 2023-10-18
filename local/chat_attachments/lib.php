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
 * Functions for the plugin
 */
function local_chat_attachments_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options=array()
) {
    /**
     * Is it the correct filearea we support?
     */
    if ($filearea !== 'chat_attachment') {
        return false;
    }
    $systemContext = context_system::instance();
    $itemId = array_shift($args);
    $fileName = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }
    $fs = get_file_storage();
    $file = $fs->get_file(
        $systemContext->id,
        'local_chat_attachments',
        $filearea,
        $itemId,
        $filepath,
        $fileName
    );
    if (!$file) {
        /**
         * Try moving the file from Draft to Our Context and check again
         */
        file_save_draft_area_files(
            $itemId,
            $systemContext->id,
            'local_chat_attachments',
            $filearea,
            $itemId,
            []
        );

        $file = $fs->get_file(
            $systemContext->id,
            'local_chat_attachments',
            $filearea,
            $itemId,
            $filepath,
            $fileName
        );
        if (!$file) {
            return false;
        }
        /**
         * Fire off the compression script.
         */
        exec('php ' . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'compress_attachment.php ' . $itemId . ' > /dev/null 2>&1 &');
    }
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 *
 * Extend page navigation
 *
 * @param global_navigation $nav
 */
function local_well_settings_extends_navigation(global_navigation $nav) {
    return local_well_settings_extend_navigation($nav);
}

/**
 *
 * Extend navigation to show the pages in the navigation block
 *
 * @param global_navigation $nav
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_well_settings_extend_navigation(global_navigation $nav) {
    global $CFG;
    $context = context_system::instance();
    $pluginname = get_string('pluginname', 'local_well_settings');
    if (is_siteadmin()) {
        $mainnode = $nav->add(
            get_string('pluginname', 'local_well_settings'),
            new moodle_url($CFG->wwwroot . '/admin/settings.php?section=managelocalchatattachments'),
            navigation_node::TYPE_CONTAINER,
            'local_well_settings',
            'local_well_settings',
            new pix_icon('settings', $pluginname, 'local_well_settings')
        );
        $mainnode->showinflatnavigation = true;
    }
}
