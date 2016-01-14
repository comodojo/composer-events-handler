<?php namespace Comodojo\Composer;

/** 
 * Composer Events retry handler.
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

abstract class Retry extends Logger {
    
    /**
     * Lists of methods to recall
     *
     * @var string
     */
    private $recall = array();
    
    /**
     * Enqueue a specific method of a class to be re-executed
     *
     * @param  string $method  Method to run
     * @param  string $class   Name of the class to retry
     */
    protected function appendRetry($method, $class) {
    	
    	if (!isset($this->recall[$method])) {
    		
    		$this->recall[$method] = array();
    		
    	}
    	
    	if (in_array($className, $this->recall[$method])) {
    		
    		return false;
    		
    	} else {
    		
    		array_push($this->recall[$method], $className);
    		
    		return true;
    		
    	}
    	
    }
    
    /**
     * Re-execute enqueued procedures
     */
    protected function doRetry() {
    	
    	foreach ($this->recall as $method => $classes) {
	    	if (count($classes) > 0) {
	    		
				$this->sendToLog("\nRunning method '" . $method . "' of previously failed procedures:\n\n");
				
				$this->runPackageInstallation($classes, $method);
				
	    	}
    	}
		
		$this->sendToLog("\n");
    	
    }
    
    /**
     * Execute all the installation procedures of a single package and handles the retry exception
     *
     * @param  array  $classes List of classes to run
     * @param  string $method  Method to run
     */
    abstract protected function runPackageInstallation($classes, $method);
    
}