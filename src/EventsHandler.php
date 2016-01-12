<?php namespace Comodojo\Composer;

use \Composer\Composer;
use \Comodojo\Exception\RetryException;
use \Comodojo\Exception\EventException;
use \Exception;

/** 
 * Composer Events Handler. This class must be extended 
 * in order to create an installation procedure
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

abstract class EventsHandler {
	
    /**
     * Reference to the Composer instance
     *
     * @var \Composer\Composer
     */
	protected $composer = null;
	
    /**
     * Class constructor
     * 
     * @param  \Composer\Composer $composer Reference to the composer instance
     * 
     * @throws \Comodojo\Exception\EventException
     */
	public function __construct(Composer $composer) {
		
		$this->composer = $composer;
		
	}

    /**
     * The following methods must be implemented by a child class with the code 
     * that will be executed after a particular event is fired
     * 
     * @throws \Comodojo\Exception\EventException
     */
    //public function install()   {}
    //public function update()    {}
    //public function uninstall() {}
    //public function finalize()  {}
    
    /**
     * Retry the execution of a method. 
     * When a method can only work when some dependencies are satisfied, 
     * using this method can solve the problem. If the execution fails for any reason,
     * it will be re-executed at the end of the installation process (when, hopefully, all the dependencies are solved)
     *
     * @param   string  $func Name of the function
     * 
     * @throws \Comodojo\Exception\RetryException
     * @throws \Comodojo\Exception\EventException
     */
    protected function retry($func) {
    	
    	if (method_exists($this, $func)) {
	    	try {
	    		
	    		call_user_func(array($this, $func));
	    		
	    	} catch (Exception $e) {
	    		
	    		throw new RetryException($e->getMessage());
	    		
	    	}
	    	
    	} else {
    		
    		throw new EventException("Method $func does not exists in class " . get_class($this));
    		
    	}
    	
    }

}
