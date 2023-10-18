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
 * A utility for deciphering the paramters of an attachment
 * <attachment type="audio" id="20782644" filepath="/" filename="audio_20210310142799.aac">
 */
class Attachment
{
    /**
     * The type of attachment
     *
     * @var string
     * @access public
     */
    public $type = '';
    /**
     * The id of the attachment
     *
     * @var string
     * @access public
     */
    public $id = 0;
    /**
     * The file path of attachment
     *
     * @var string
     * @access public
     */
    public $filepath = '';
    /**
     * The file name of attachment
     *
     * @var string
     * @access public
     */
    public $filename = '';

    /**
     * Builds the class
     *
     * @param string $message The message
     */
    public function __construct($message)
    {
        $this->type = $this->getArgument('type', $message);
        $this->id = intval($this->getArgument('id', $message));
        $this->filepath = $this->getArgument('filepath', $message);
        $this->filename = $this->getArgument('filename', $message);
    }

    /**
     * Is this a valid attachment?
     *
     * @param string $message The message
     * @return boolean        yes|no
     * @access public
     */
    public static function isAttachment($message)
    {
        return (strpos($message, '<attachment') !== false);
    }

    /**
     * Return this object to an array of data
     *
     * @return array The array of data
     * @access public
     */
    public function toArray()
    {
        return [
            'type'      =>  $this->type,
            'id'        =>  intval($this->id),
            'filepath'  =>  $this->filepath,
            'filename'  =>  $this->filename
        ];
    }

    /**
     * Get a string version of the attachment data
     *
     * @return string   Details about the attachment
     * @access public
     */
    public function toString()
    {
        return '<attachment type="' . $this->type . '" id="' . $this->id . '" filepath="' . $this->filepath . '" filename="' . $this->filename . '">';
    }

    /**
     * Get the argument by name
     *
     * @param  string $name    The name of the argument
     * @param  string $message The message
     * @return string          The value or ''
     * @access private
     */
    private function getArgument($name, $message)
    {
        $matches = [];
        preg_match('/' . $name . '="(.*?)"/', $message, $matches);
        if (count($matches) > 1) {
            return $matches[1];
        } else {
            return '';
        }
    }
}
