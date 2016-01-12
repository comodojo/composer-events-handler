<?php namespace Comodojo\Composer;

/**
 * The composer framework doesn't import the autoload during execution, 
 * so, in order to work with installed classes, we must include it ourselves
 */
require __DIR__ . "/../../../autoload.php";

use \Composer\Composer;
use \Composer\EventDispatcher\EventSubscriberInterface;
use \Composer\IO\IOInterface;
use \Composer\Plugin\PluginInterface;
use \Composer\Plugin\PackageInterface;
use \Composer\Script\ScriptEvents;
use \Composer\Script\PackageEvent;
use \Composer\Script\CommandEvent;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Formatter\LineFormatter;
use \Comodojo\Exception\RetryException;
use \Comodojo\Exception\EventException;
use \Exception;

/** 
 * Composer Events Handler. This class is a composer plugins 
 * that execute installation procedures after "composer" execution.
 * 
 * In order to make it works, you must create a class that 
 * extends the \Comodojo\Composer\EventsHandler class implementing 
 * the "finalize" method, then you have to add the tag "composer-events-handler" 
 * in the "extra" section of your "composer.json" listing all the
 * class that must be executed.
 * 
 * Example:
 * 
 * {
 *     [...]
 *     "extra": {
 *         "composer-events-handler": [
 *             "MyApp\\Setup\\SetupProcedureClass1",
 *             "MyApp\\Setup\\SetupProcedureClass2",
 *             "MyApp\\Setup\\SetupProcedureClass3"
 *         ]
 *     }
 * }
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

class Plugin implements PluginInterface, EventSubscriberInterface {
    
    /**
     * Counter of errors encountered
     *
     * @var int
     */
    private $err = 0;
    
    /**
     * Reference to the Composer instance
     *
     * @var \Composer\Composer
     */
    private $composer = null;
    
    /**
     * Path of the log file
     *
     * @var string
     */
    private $logfile = "./composer-events.log";
    
    /**
     * Class name
     *
     * @var string
     */
    private $className = "";
    
    /**
     * Reference to the logger
     *
     * @var \Monolog\Logger
     */
    private $log = null;
    
    /**
     * Reference to the console
     *
     * @var \Monolog\Logger
     */
    private $console = null;
    
    /**
     * Lists of methods to recall
     *
     * @var string
     */
    private $recall = array();

    /**
     * Plugin activator, this method is called by the composer framework
     * therefore it must be implemented, but we just use it to load the loggers
     * 
     */
    public function activate(Composer $composer, IOInterface $io) {
    	
    	$dateFormat       = "Y-m-d H:i:s";
    	$logFormatter     = new LineFormatter("[%datetime%] %context% %message%\n", $dateFormat);
    	$consoleFormatter = new LineFormatter("%message%", $dateFormat, true);
    	
    	$streamToFile    = new StreamHandler($this->logfile, Logger::INFO);
    	$streamToFile->setFormatter($logFormatter);
    	$streamToConsole = new StreamHandler('php://stdout', Logger::INFO);
    	$streamToConsole->setFormatter($consoleFormatter);
    	
    	$this->log = new Logger('log');
    	$this->log->pushHandler($streamToFile);
    	
    	$this->console = new Logger('console');
    	$this->console->pushHandler($streamToConsole);
    	
    	$this->composer = $composer;
        
        $extra = $composer->getPackage()->getExtra();
        
        if (isset($extra['composer-events-log'])) {
        	
        	$this->logfile = $extra['composer-events-log'];
        	
        }
        
    }

    /**
     * Static method to retrieve the list of events to handle
     * 
     * @return  array   $eventList  An array containing the list of events and their listeners
     */
    public static function getSubscribedEvents() {
    	
        return array(
            ScriptEvents::POST_INSTALL_CMD      => 'onPostInstallCMD',
            ScriptEvents::POST_UPDATE_CMD       => 'onPostInstallCMD',
            ScriptEvents::POST_PACKAGE_INSTALL  => 'onPostPackageInstall',
            ScriptEvents::POST_PACKAGE_UPDATE   => 'onPostPackageUpdate',
            ScriptEvents::PRE_PACKAGE_UNINSTALL => 'onPrePackageUninstall'
        );
        
    }
    
    /**
     * Listener to the POST_INSTALL_CMD and POST_UPDATE_CMD events
     * It contains the code that launch the execution of the finalize precedures
     *
     * @param  \Composer\Script\CommandEvent $event Event to handle
     *
     */
    public function onPostInstallCMD(CommandEvent $event) {
    	
    	$packages = $event->getComposer()
    		->getRepositoryManager()
    		->getLocalRepository()
    		->getPackages();
    	
    	foreach ($packages as $package) {
    		
    		$this->onEventHandler($package, "finalize");
    		
    	}
    	
    	foreach ($this->recall as $method => $classes) {
	    	if (count($classes) > 0) {
	    		
				$this->sendToLog("\nRunning method '" . $method . "' of previously failed procedures:\n\n");
				
				$this->runPackageInstallation($classes, $method);
				
	    	}
    	}
		
		$this->sendToLog("\n");
    	
    }
    
    /**
     * Listener to the POST_PACKAGE_INSTALL events
     * It contains the code that launch the execution of the installation precedures
     *
     * @param  \Composer\Script\PackageEvent $event Event to handle
     *
     */
    public function onPostPackageInstall(PackageEvent $event) {
    	
    	$package = $event->getOperation()->getPackage();
    	
    	$this->onEventHandler($package, "install");
    	
    }
    
    /**
     * Listener to the POST_PACKAGE_UPDATE events
     * It contains the code that launch the execution of the update precedures
     *
     * @param  \Composer\Script\PackageEvent $event Event to handle
     *
     */
    public function onPostPackageUpdate(PackageEvent $event) {
    	
    	$package = $event->getOperation()->getPackage();
    	
    	$this->onEventHandler($package, "update");
    	
    }
    
    /**
     * Listener to the PRE_PACKAGE_UNINSTALL events
     * It contains the code that launch the execution of the update precedures
     *
     * @param  \Composer\Script\PackageEvent $event Event to handle
     *
     */
    public function onPrePackageUninstall(PackageEvent $event) {
    	
    	$package = $event->getOperation()->getPackage();
    	
    	$this->onEventHandler($package, "uninstall");
    	
    }
    
    
    /**
     * Generic event handler, it runs the execution of the method associated to the event 
     *
     * @param  \Composer\Script\PackageEvent $event  Event to handle
     * @param  string                        $method Method to run
     *
     */
    private function onEventHandler(PackageInterface $package, $method) {
    		
    	$extra = $package->getExtra();
    		
		if (isset($extra['composer-events-handler'])) {
			
			$this->sendToLog(
				sprintf("\nRunning %s procedures for package '%s':\n\n", $method, $package->getName())
			);
			
			$this->runPackageInstallation($extra['composer-events-handler'], $method);
			
		}
    	
    }
    
    /**
     * Execute all the installation procedures of a single package and handles the retry exception
     *
     * @param  array  $classes List of classes to run
     * @param  string $method  Method to run
     */
    private function runPackageInstallation($classes, $method) {
		
		foreach($classes as $class) {
			
			if ($this->setProcessingClass($class)) {
			
				try {
					
					$this->installClass($method, $extra);
					
					$this->sendToLog("OK\n");
					
				} catch (RetryException $e) {
					
					$this->sendToLog("Retry\n");
					
					$this->sendToLog(
						$e->getMessage(),
						$this->log
					);
					
				} catch (EventException $e) {
					
					$this->err++;
					
					$this->sendToLog("Failed\n");
					
					$this->sendToLog(
						$e->getMessage(),
						$this->log
					);
					
				}
			
			}
			
		}
    			
    }
    
    /**
     * Check if a class is a subclass of the Installer class and then execute it
     * 
     * @param  string $method Method to run
     *
     * @throws \Comodojo\Exception\RetryException
     * @throws \Comodojo\Exception\EventException
     */
    private function installClass($method) {
    	
    	$className = $this->className;
    	
		try {
			
			if (is_subclass_of($className, 'Comodojo\Composer\EventsHandler')) {
			
				$installer = new $className($this->composer);
				
				if (method_exists($installer, $method)) {
					
					call_user_func(array($installer, $method));
					
				}
				
			} else {
				
				throw new Exception("$className is not an implementation of the 'EventsHandler' class");
				
			}
			
		} catch (RetryException $e) {
			
			if (in_array($className, $this->recall[$method])) {
				
				throw new EventException("Installation still not works after retry: " . $e->getMessage());
				
			} else {
				
				if (!isset($this->recall[$method])) $this->recall[$method] = array();
				
				array_push($this->recall[$method], $className);
				
				throw $e;
				
			}
			
		} catch (Exception $e) {
			
			throw new EventException($e->getMessage());
			
		}
    	
    }
    
    /**
     * Set the Setup Class to process
     *
     * @param  string $className
     *
     * @return boolean $exists Whether the class exists or not
     */
    private function setProcessingClass($className) {
    		
		$this->className = $className;
		
		$className = preg_replace('/\\\\/', "::", $className);
		
		$this->sendToLog(sprintf("   %-60s", $className));
    	
    	if (class_exists($this->className)) {
			
			return true;
    		
    	} else {
    		
    		$this->err++;
    		
    		$this->sendToLog("Not Found\n");
					
			$this->sendToLog(
				"The class do not exists",
				$this->log
			);
    		
    		$this->className = "";
    		
    		return false;
    		
    	}
    	
    }
    
    /**
     * Log information to the desired destination
     *
     * @param string $message  Message to log
     * @param string $logger   Where to log the message (optional, by default it prints on screen)
     */
    private function sendToLog($message, $logger = null) {
    	
    	if (is_null($logger)) $logger = $this->console;
    	
    	$logger->addInfo($message, array($this->className));
		
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