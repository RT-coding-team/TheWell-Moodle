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
 * A utility for interacting with the Moodle's file storage library
 */
class FileStorageUtility
{
    /**
     * The name of the component to store files in
     * @var string
     * @access public
     */
    public $component = 'local_chat_attachments';

    /**
     * The name of the file area where to store files.
     *
     * @var string
     * @access public
     */
    public $fileArea = 'chat_attachment';

    /**
     * An instance of Moodle's database
     *
     * @var $DB
     */
    protected $db = null;
    /**
     * Our file storage context
     *
     * @var file_storage
     * @access protected
     */
    protected $storage = null;

    /**
     * The id of the context to store files.
     *
     * @var integer
     * @access protected
     */
    protected $contextId = -1;

    /**
     * Set up the FileStorage utility
     *
     * @param object        $database       Moodle's database
     * @param file_storage  $fileStorage    Moodle's file storage system
     * @param integer       $contextId      The id of the context to store files
     *
     * @access public
     */
    public function __construct($database, $fileStorage, $contextId)
    {
        $this->db = $database;
        $this->storage = $fileStorage;
        $this->contextId = $contextId;
    }

    /**
     * Delete the given file.
     *
     * @param  integer $id  The id of the file to delete
     * @return void
     */
    public function delete($id)
    {
        $details = $this->findById($id);
        $file = $this->storage->get_file(
            $this->contextId,
            $this->component,
            $this->fileArea,
            $id,
            $details->filepath,
            $details->filename
        );
        if ($file) {
            $file->delete();
        }
    }

    /**
     * Find the file by the given id
     *
     * @param  integer  $id     The id of the given file
     * @return object|null      The file object of null if missing
     *
     * @access public
     */
    public function findById($id)
    {
        $file = $this->db->get_record_sql(
            'SELECT * FROM {files} WHERE itemid = ? AND filearea = ? AND component = ? AND filename != ?',
            [$id, $this->fileArea, $this->component, '.']
        );
        if (!$file) {
            return null;
        }
        return $file;
    }

    /**
     * Retrieve a file from storage and save to a temp directory.
     *
     * @param   integer     $id         The id of the file
     * @param   string      $filepath   The path of the file
     * @param   string      $filename   The name of the file
     * @return  string                  The path to the temporary file
     *
     * @access public
     */
    public function retrieve($id, $filepath, $filename)
    {
        $file = $this->storage->get_file(
            $this->contextId,
            $this->component,
            $this->fileArea,
            $id,
            $filepath,
            $filename
        );
        if ($file) {
            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
            $file->copy_content_to($path);
            return $path;
        } else {
            return '';
        }
    }

    /**
     * Stores the file into moodle
     *
     * @param   string      $filename       The name of the file
     * @param   string      $tempFile       Path to the temporary file
     * @return  integer                     The id for the item
     *
     * @access public
     */
    public function store($filename, $tempFile)
    {
        $id = $this->getFileId();
        $record = [
            'contextid' =>  $this->contextId,
            'component' =>  $this->component,
            'filearea'  =>  $this->fileArea,
            'itemid'    =>  $id,
            'filepath'  =>  '/',
            'filename'  =>  $filename
        ];
        $file = $this->storage->create_file_from_pathname($record, $tempFile);
        return $id;
    }

    /**
     * Update the current file.
     *
     * @param  integer  $id         The id of the file
     * @param  string   $filename   The filename
     * @param  string   $tempFile   The path to the temp file
     * @return void
     */
    public function update($id, $filename, $tempFile)
    {
        $this->delete($id);
        $record = [
            'contextid' =>  $this->contextId,
            'component' =>  $this->component,
            'filearea'  =>  $this->fileArea,
            'itemid'    =>  $id,
            'filepath'  =>  '/',
            'filename'  =>  $filename
        ];
        $file = $this->storage->create_file_from_pathname($record, $tempFile);
    }

    /**
     * Get a file id to store the file.  This method is adapted from file_get_unused_draft_itemid()
     * in lib/filelib.php
     *
     * @return integer  The id to use.
     * @access private
     */
    private function getFileId()
    {
        $id = rand(1, 999999999);
        while (
            $files = $this->storage->get_area_files(
                $this->contextId,
                $this->component,
                $this->fileArea,
                $id
            )
        ) {
            $id = rand(1, 999999999);
        }

        return $id;
    }
}
