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
 * A custom extension of admin_setting_configtext to validate a text field to be:
 *
 * (1) 12 characters or less (blank is ok)
 * (2) only letters and numbers
 * (3) no spaces
 *
 */
class admin_setting_configtext_custom extends admin_setting_configtext {
    /**
     * The maximum length of the string
     * @var integer
     */
    public $maximumLength = 12;

    /**
     * Validate the field.
     *
     * @param  string $data The provided data
     * @return mixed        true if ok string if error found
     */
    public function validate($data) {
        if (empty($data)) {
            return true;
        }
        if (strlen($data) > $this->maximumLength) {
            return new lang_string('field_too_long', 'local_chat_attachments', $this->maximumLength);
        }
        if (strpos($data, ' ') !== false) {
            return new lang_string('field_no_spaces', 'local_chat_attachments');
        }
        if (!ctype_alnum($data)) {
            return new lang_string('field_letters_numbers_only', 'local_chat_attachments');
        }
        return true;
    }
}
