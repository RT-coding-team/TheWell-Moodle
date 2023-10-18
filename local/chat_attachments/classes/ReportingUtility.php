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
 * This class creates a JSON file for reporting script progress to the browser.
 * The file is recreated each time this class intialized
 */
class ReportingUtility
{
    /**
     * What line break we will use when printing to the screen.
     *
     * @var string
     * @access public
     */
    public $printLineBreak = "\r\n";

    /**
     * The name of the file that is created.
     *
     * @var string
     * @access protected
     */
    protected $filename = 'report.json';

    /**
     * The reporting file.
     *
     * @var string
     * @access protected
     */
    protected $logFile;

    /**
     * Do you want to log to the file, or echo out.  If true, it logs to the file.
     * If false, it logs to console.
     *
     * @var boolean
     * @access protected
     */
    protected $toFile = true;

    /**
     * The data that is written to the JSON file.
     *
     * errors:          An array of errors that occurred
     * logs:            An array of log messages
     * payloads:        Data being sent to or received from the API (json encoded string)
     * progress:        If we are current in a progress loop it will contain keys: current, total, and title.
     * results:         An array of results for the task.
     * steps:           Describes the step and it's current status (pending, started, errored, or completed)
     * support_token:   Set to a random token when an error is detected. Used to send support emails.
     *
     * @var array
     * @access protected
     */
    protected $data = [
        'errors'    =>  [],
        'logs'      =>  [],
        'payloads'  =>  [],
        'progress'  =>  null,
        'results'   =>  [],
        'steps'     =>  [
            'script'                        =>  'pending',
            'check_last_sync'               =>  'pending',
            'sending_logs'                  =>  'pending',
            'get_settings'                  =>  'pending',            
            'sending_roster'                =>  'pending',
            'sending_messages'              =>  'pending',
            'sending_attachments'           =>  'pending',
            'receiving_messages'            =>  'pending'
        ],
        'support_token' =>  null
    ];

    /**
     * Sets up the utility
     *
     * @param string    $directory  The directory to store the file. (default: basename(__DIR__))
     * @param boolean   $toFile     Do you want to store the results to a file? (default: true)
     *
     * @throws InvalidArgumentException If the directory does not exist
     */
    public function __construct($directory = null, $toFile = true)
    {
        $this->toFile = $toFile;
        if (!$directory) {
            $directory = basename(__DIR__);
        }
        if (!file_exists($directory)) {
            throw new InvalidArgumentException(
                'You must provide a valid directory for storing the report file.'
            );
        }
        $this->logFile = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->filename;
    }

    /**
     * Clear and reset the file.
     *
     * @return void
     * @access public
     */
    public function clear()
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
        $this->save();
    }

    /**
     * Report an error
     *
     * @param   string $message     The message of the error
     * @param   string $category    A category to group log messages (default: error)
     * @return  void
     * @access  public
     */
    public function error($message, $category = 'error')
    {
        $error = [
            'category'      =>  $category,
            'message'       =>  $message,
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        $this->data['errors'][] = $error;
        $this->generateSupportToken(false, false);
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('ERROR', $error);
        }
    }

    /**
     * Generates a random support token.  This protects from bombarding the support email.
     *
     * @param   boolean     $save       Do you want to save the report? (default: true)
     * @param   boolean     $overwrite  Do you want to overwrite the current value? (default: true)
     * @return void
     * @access public
     */
    public function generateSupportToken($save = true, $overwrite = true)
    {
        if (!$this->toFile) {
            // We don not have the data load it, so load it.
            $this->data = $this->read(true);
            if (!$this->data) {
                return null;
            }
        }
        if ((!$this->data['support_token']) || ($overwrite)) {
            $this->data['support_token'] = md5(uniqid(rand(), true));
        }
        if ($save) {
            $this->save();
        }
    }

    /**
     * Get the path to the log file.
     *
     * @return string The path to the log file.
     * @access public
     */
    public function getLogFilePath()
    {
        return $this->logFile;
    }

    /**
     * Report a message
     *
     * @param  string $message The message to log
     * @param   string $category    A category to group log messages (default: info)
     * @return void
     * @access public
     */
    public function info($message, $category = 'info')
    {
        $item = [
            'category'      =>  $category,
            'message'       =>  $message,
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        $this->data['logs'][] = $item;
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('INFO', $item);
        }
    }

    /**
     * Read the contents of the log file.
     *
     * @param   $toArray            Do you want the response as an array (default: false)
     * @return  stdClass|array|null The JSON decoded
     * @access  public
     */
    public function read($toArray = false)
    {
        $contents = file_get_contents($this->logFile);
        if ((!isset($contents)) || ($contents === '')) {
            return null;
        }
        return json_decode($contents, $toArray);
    }

    /**
     * Save a payload for a specific request.
     *
     * @param  string   $key        The key of the payload. Use _ for seperating words.
     * @param  array    $payload    The actual payload.
     * @return void
     * @access public
     */
    public function savePayload($key, $payload = [])
    {
        if (count($payload) === 0) {
            return;
        }
        $this->data['payloads'][$key] = json_encode($payload, JSON_PRETTY_PRINT);
        $prettyKey = ucwords(str_replace('_', ' ', $key));
        $item = [
            'category'      =>  'payload',
            'message'       =>  'Storing payload for ' . $prettyKey . '(' . $key . ')',
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('PAYLOAD', $item);
        }
    }

    /**
     * Save a specific result of the script.
     *
     * @param  string   $key   The key of the result. Use _ for seperating words.
     * @param  mixed    $value The value of the result
     * @return void
     * @access public
     */
    public function saveResult($key, $value)
    {
        $this->data['results'][$key] = $value;
        $prettyKey = ucwords(str_replace('_', ' ', $key));
        $item = [
            'category'      =>  'result',
            'message'       =>  $prettyKey . ' = ' . $value,
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('RESULT', $item);
        }
    }

    /**
     * Save a given step to the payload
     *
     * @param  string $step   The step to save. Must be a key of $this->data['steps'].
     * @param  string $status The status of the step. Must be pending, started, errored, or completed.
     * @return void
     * @access public
     *
     * @throws InvalidArgumentException If the step is not a key of $this->data['steps']
     * @throws InvalidArgumentException If the status is not pending, started, errored, or completed.
     */
    public function saveStep($step, $status)
    {
        if (!array_key_exists($step, $this->data['steps'])) {
            throw new InvalidArgumentException('You must provide a valid step.');
        }
        if (!in_array($status, ['pending', 'started', 'errored', 'completed'])) {
            throw new InvalidArgumentException(
                'You must provide a valid status: pending, started, errored, or completed.'
            );
        }
        $this->data['steps'][$step] = $status;
        $prettyKey = ucwords(str_replace('_', ' ', $step));
        // The internal method handles saving.
        if ($status === 'errored') {
            $this->error($prettyKey . ' set to ' . $status . '.', 'steps');
        } else {
            $this->info($prettyKey . ' set to ' . $status . '.', 'steps');
        }
    }

    /**
     * Start progress reporting
     *
     * @param  string   $title The title of the progress being tracked
     * @param  integer  $total The total to complete
     * @return void
     * @access public
     */
    public function startProgress($title, $total)
    {
        $this->data['progress'] = [
            'current'   =>  0,
            'error'     =>  0,
            'title'     =>  $title,
            'total'     =>  $total
        ];
        $item = [
            'category'      =>  'progress',
            'message'       =>  'Starting progress for ' . $title . '.',
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('PROGRESS', $item);
        }
    }

    /**
     * Get the total success in the progress
     *
     * @return integer  The total successes
     * @access public
     */
    public function getProgressSuccess()
    {
        if (!$this->data['progress']) {
            return 0;
        }
        return $this->data['progress']['current'];
    }

    /**
     * Report progress success. Increments count.
     *
     * @return void
     * @access public
     */
    public function reportProgressSuccess()
    {
        if (!$this->data['progress']) {
            return;
        }
        $this->data['progress']['current'] += 1;
        $item = [
            'category'      =>  'progress',
            'message'       =>  'Current progress ' . $this->data['progress']['current'] . ' of ' . $this->data['progress']['total'] . '.',
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('PROGRESS', $item);
        }
    }

    /**
     * Get the total errors in the progress
     *
     * @return integer  The total errors
     * @access public
     */
    public function getProgressError()
    {
        if (!$this->data['progress']) {
            return 0;
        }
        return $this->data['progress']['error'];
    }

    /**
     * Report progress failure. Increments error.
     *
     * @return void
     * @access public
     */
    public function reportProgressError()
    {
        if (!$this->data['progress']) {
            return;
        }
        $this->data['progress']['error'] += 1;
        $item = [
            'category'      =>  'progress',
            'message'       =>  'Current errors ' . $this->data['progress']['error'] . ' of ' . $this->data['progress']['total'] . '.',
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('PROGRESS', $item);
        }
    }

    /**
     * Stop progress reporting
     *
     * @return void
     * @access public
     */
    public function stopProgress()
    {
        if (!$this->data['progress']) {
            return;
        }
        $item = [
            'category'      =>  'progress',
            'message'       =>  'Stopping progress for ' . $this->data['progress']['title'] . '.',
            'pretty_time'   =>  date('g:i:s A'),
            'timestamp'     =>  time()
        ];
        $this->data['progress'] = null;
        if ($this->toFile) {
            $this->save();
        } else {
            $this->print('PROGRESS', $item);
        }
    }

    /**
     * Print message to screen
     *
     * @param   string  $type       The type of message
     * @param   array   $message    An array of details: message, timestamp, pretty_time
     * @return void
     * @access protected
     */
    protected function print($type, $message)
    {
        echo $message['pretty_time'] . ': [' . $type . '] ' . $message['message'] . ' (' . $message['category'] . ')' . $this->printLineBreak;
    }

    /**
     * Save the data to the file.
     *
     * @return void
     * @access protected
     */
    protected function save()
    {
        file_put_contents($this->logFile, json_encode($this->data));
    }
}
