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
 * This class keeps track of messages we received, but were unable to download the
 * attachment.  We try to fire it at a later time.
 */
class FailedMessagesUtility
{
    /**
     * The name of the file that is created.
     *
     * @var string
     * @access protected
     */
    protected $filename = 'failed-messages.json';

    /**
     * An array of the failed messages stored in the JSON file.
     *
     * @var array
     * @access protected
     */
    protected $messages = [];

    /**
     * The storage file.
     *
     * @var string
     * @access protected
     */
    protected $storageFile;

    /**
     * Sets up the utility
     *
     * @param string    $directory  The directory to store the file. (default: basename(__DIR__))
     */
    public function __construct($directory = null)
    {
        if (!$directory) {
            $directory = basename(__DIR__);
        }
        if (!file_exists($directory)) {
            throw new InvalidArgumentException(
                'You must provide a valid directory for storing the report file.'
            );
        }
        $this->storageFile = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->filename;
        if (file_exists($this->storageFile)) {
            $contents = file_get_contents($this->storageFile);
            if ($contents) {
                $this->messages = json_decode($contents, true);
            }
        }
    }

    /**
     * Add a message to the failed messages
     *
     * @param integer   $id             The id of the message
     * @param integer   $senderId       The sender's id
     * @param integer   $conversationId The conversation id
     * @param string    $message        The message
     * @param array     $attachment     The attachment details
     * @return void
     * @access public
     */
    public function add($id, $senderId, $conversationId, $message)
    {
        if ($this->exists($id)) {
            return;
        }
        $this->messages[] = [
            'conversation_id'   =>  $conversationId,
            'id'                =>  $id,
            'message'           =>  $message,
            'sender_id'         =>  $senderId
        ];
        $this->save();
    }

    /**
     * Get all the messages
     *
     * @return array The missing messages
     * @access public
     */
    public function all()
    {
        return $this->messages;
    }

    /**
     * Checks if the message is already archived.
     *
     * @param  integer $id The id of the message
     * @return boolean     exists?
     * @access public
     */
    public function exists($id)
    {
        $found = false;
        foreach ($this->messages as $key => $message) {
            if ($message['id'] === $id) {
                $found = true;
                break;
            }
        }
        return $found;
    }

    /**
     * Remove a message from the failed messages.
     *
     * @param  integer $id  The id of the message to remove
     * @return boolean      Successfully removed?
     * @access public
     */
    public function remove($id)
    {
        $removeKey = -1;
        foreach ($this->messages as $key => $message) {
            if ($message['id'] === $id) {
                $removeKey = $key;
                break;
            }
        }
        if ($removeKey > -1) {
            unset($this->messages[$removeKey]);
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Save the data to the file.
     *
     * @return void
     * @access protected
     */
    protected function save()
    {
        file_put_contents($this->storageFile, json_encode($this->messages));
    }
}
