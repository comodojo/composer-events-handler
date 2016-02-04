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
 * @license     MIT
 *
 * LICENSE:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
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
	public function install()   {} // Called after the installation of the package (including root package)
	public function update()    {} // Called after the package has been updated
	public function uninstall() {} // Called before the package is uninstalled (you can use it to clean up)
	public function finalize()  {} // Always called after composer has finished doing its job (install, update, create-project)

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
