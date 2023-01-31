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
 * Base response object for local_ws_enrolcohort.
 *
 * @package     local_ws_enrolcohort
 * @author      Donald Barrett <donald.barrett@learningworks.co.nz>
 * @copyright   2018 onwards, LearningWorks ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ws_enrolcohort\responses;

// No direct access.
defined('MOODLE_INTERNAL') || die();

abstract class response implements base {

    /**
     * @var int $id             The id of the object this is representing.
     */
    protected $id;

    /**
     * @var string $object      The name of the object this is representing.
     */
    protected $object;

    /**
     * response constructor.
     *
     * @param int $id
     * @param string $object
     */
    public function __construct($id = 0, $object = '') {
        $this->id       = $id;
        $this->object   = $object;
    }

    /**
     * Return this response object as an array.
     *
     * @return array|mixed
     */
    public function to_array() {
        $return = [];

        foreach ($this as $key => $value) {
            $return[$key] = $value;
        }

        return $return;
    }
}