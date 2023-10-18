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
 * This class parses the settings json and gets the specific setting.
 */
class Settings
{
    /**
     * The settings file location
     *
     * @var string
     * @static
     */
    public static $settingsFile = '/usr/local/connectbox/brand.txt';

    /**
     * Get the chosen setting
     *
     * @param   string  $key            The setting key
     * @param   mixed   $defaultValue   The default value to return (default: null)
     * @return  mixed                   The value or the $defaultValue
     */
    public static function get($key, $defaultValue = null) {
        if (!file_exists(self::$settingsFile)) {
            return $defaultValue;
        }
        $contents = file_get_contents(self::$settingsFile);
        $data = json_decode($contents, true);
        if (!array_key_exists($key, $data)) {
            return $defaultValue;
        }
        return $data[$key];
    }
}
