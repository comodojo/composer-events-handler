<?php namespace Comodojo\Composer;

use \Monolog\Logger as MonoLogger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Formatter\LineFormatter;

/** 
 * Composer Events Logger.
 * 
 * @package     Comodojo Spare Parts
 * @author      Marco Castiello <marco.castiello@gmail.com>
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
 * @license     GPL-3.0+
 *
 * LICENSE:
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class Logger {
    
    /**
     * Counter of errors encountered
     *
     * @var int
     */
    private $err = 0;
    
    /**
     * Path of the log file
     *
     * @var string
     */
    protected $logfile = "./composer-events.log";
    
    /**
     * Class name
     *
     * @var string
     */
    protected $className = "";
    
    /**
     * Reference to the logger
     *
     * @var \Monolog\Logger
     */
    protected $log = null;
    
    /**
     * Reference to the console
     *
     * @var \Monolog\Logger
     */
    protected $console = null;
    
    /**
     * Initialize the logs
     */
    protected function initLogs() {
    	
    	$dateFormat       = "Y-m-d H:i:s";
    	$logFormatter     = new LineFormatter("[%datetime%] %context% %message%\n", $dateFormat);
    	$consoleFormatter = new LineFormatter("%message%", $dateFormat, true);
    	
    	$this->log        = $this->createLogger('log',     $logFormatter,     $this->logfile);
    	$this->console    = $this->createLogger('console', $consoleFormatter, 'php://stdout');
    	
    	
    }
    
    /**
     * Create a log
     *
     * @param string        $name      Name of the log
     * @param LineFormatter $formatter Formatting object for log entries
     * @param string        $output    Destination of the log entries (filename or stream)
     *
     * @return MonoLogger   $logger    Reference object to the logger created
     */
    private function createLogger($name, LineFormatter $formatter, $output) {
    	
    	$stream = new StreamHandler($output, MonoLogger::INFO);
    	$stream->setFormatter($formatter);
    	
    	$logger = new MonoLogger($name);
    	$logger->pushHandler($stream);
    	
    	return $logger;
    	
    }
    
    /**
     * Log information to the desired destination
     *
     * @param string $message  Message to log
     * @param string $logger   Where to log the message (optional, by default it prints on screen)
     */
    protected function sendToLog($message, $logger = null) {
    	
    	if (is_null($logger)) $logger = $this->console;
    	
    	$logger->addInfo($message, array($this->className));
		
    }
    
    /**
     * Increment the error counting
     */
    protected function logError() {
    	
    	$this->err++;
		
    }
    
    
    /**
     * Class descructor, if the installation encountered errors, it shows a message to inform the user
     * 
     */
    function __destruct() {
    			
		if ($this->err > 0) {
			
			$this->sendToLog(
				sprintf("%d procedure(s) failed. For more info check the log '%s'\n\n", $this->err, $this->logfile)
			);
			
		}
    	
    }
    
}